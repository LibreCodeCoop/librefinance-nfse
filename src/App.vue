<!--
SPDX-FileCopyrightText: 2026 LibreCode Coop and contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup>
import { ref, onMounted } from 'vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
const props=defineProps({spaceId:{type:Number,required:true},embedded:{type:Boolean,default:false}})
const base='/apps/librefinance_nfse/api/spaces/'+props.spaceId
const today=new Date().toLocaleDateString('sv-SE')
const companies=ref([])
const selectedCompanyId=ref(0)
const municipalityCode=ref('')
const competence=ref(today)
const serviceCode=ref('')
const description=ref('')
const amount=ref('')
const drafts=ref([])
const nextAfterId=ref(null)
const busy=ref(false)
const canEdit=ref(false)
const error=ref('')
const success=ref('')
function money(minor){return new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'}).format(Number(minor)/100)}
function minor(value){
  const m=String(value).trim().match(/^(\d{1,10})(?:[.,](\d{1,2}))?$/)
  if (!m) throw new Error('Valor inválido')
  const result=Number(m[1])*100+Number((m[2]||'').padEnd(2,'0'))
  if(!Number.isSafeInteger(result)||result<1||result>9000000000000) throw new Error('Valor fora do limite')
  return result
}
async function api(path,method='GET',data){
  return (await axios({url:generateUrl(base+path),method,data})).data
}
async function load(){
  try {
    const [r, spaces, legalEntities]=await Promise.all([api('/drafts'),axios.get(generateUrl('/apps/librefinance/api/spaces')),
      axios.get(generateUrl('/apps/librefinance/api/spaces/'+props.spaceId+'/companies'))])
    drafts.value=r.drafts;nextAfterId.value=r.nextAfterId
    companies.value=legalEntities.data.companies.filter(c=>c.active && c.country==='BR' && /^[0-9]{14}$/.test(c.tax_id))
    if(!companies.value.some(c=>c.id===Number(selectedCompanyId.value))) selectedCompanyId.value=0
    const space=spaces.data.spaces.find(s=>Number(s.id)===props.spaceId)
    canEdit.value=['owner','manager','operator'].includes(space?.role)
  } catch(e){error.value=e.response?.data?.error||e.message}
}
async function more(){
  if(!nextAfterId.value)return
  const r=await api('/drafts?afterId='+nextAfterId.value)
  drafts.value.push(...r.drafts);nextAfterId.value=r.nextAfterId
}
async function create(){
  if(busy.value)return
  busy.value=true;error.value='';success.value=''
  try {
    const selected=companies.value.find(c=>c.id===Number(selectedCompanyId.value))
    if(!selected)throw new Error('Selecione uma empresa prestadora cadastrada.')
    await api('/drafts','POST',{
      operationKey:'nfse:draft:'+crypto.randomUUID(),
      issuerCnpj:selected.tax_id,companyId:selected.id,municipalityCode:municipalityCode.value,
      competence:competence.value,serviceCode:serviceCode.value,
      description:description.value,amountMinor:minor(amount.value),
    })
    success.value='Rascunho salvo. Nenhuma nota fiscal foi emitida.'
    await load()
    description.value='';amount.value=''
  }catch(e){error.value=e.response?.data?.error||e.message}
  finally{busy.value=false}
}
onMounted(load)
</script>
<template>
  <main class="lfn-main">
    <header v-if="!embedded"><h1>NFS-e Nacional</h1><a :href="generateUrl('/apps/librefinance/')">Voltar ao LibreFinance</a></header>
    <p class="lfn-notice">Módulo em desenvolvimento: permite preparar dados de serviços, mas NÃO envia DPS, NÃO assina XML e NÃO emite notas fiscais.</p>
    <p v-if="error" role="alert" class="lfn-error">{{error}}</p>
    <p v-if="success" role="status">{{success}}</p>
    <section class="lfn-panel">
      <h2>Rascunho de serviço</h2>
      <form v-if="canEdit && companies.length" @submit.prevent="create" class="lfn-form">
        <label>Empresa prestadora<select v-model.number="selectedCompanyId" required>
          <option :value="0" disabled>Selecione a empresa cadastrada</option>
          <option v-for="company in companies" :key="company.id" :value="company.id">{{company.legal_name}} · {{company.tax_id}}</option>
        </select></label>
        <label>CNPJ do prestador<input :value="companies.find(c=>c.id===Number(selectedCompanyId))?.tax_id || ''" readonly autocomplete="off" aria-label="CNPJ do prestador"></label>
        <label>Município IBGE<input v-model.trim="municipalityCode" required maxlength="7" placeholder="Código IBGE"></label>
        <label>Competência<input v-model="competence" required type="date"></label>
        <label>Código de serviço<input v-model.trim="serviceCode" required maxlength="15"></label>
        <label>Descrição<input v-model.trim="description" required maxlength="255"></label>
        <label>Valor (BRL)<input v-model.trim="amount" required inputmode="decimal" placeholder="0,00"></label>
        <button :disabled="busy" type="submit">Salvar rascunho (não emitir)</button>
      </form>
      <p v-else-if="!canEdit">Seu perfil permite consulta, mas não preparação de documentos.</p>
      <p v-else>Cadastre primeiro uma empresa com CNPJ no LibreFinance para preparar um rascunho fiscal vinculado.</p>
    </section>
    <section class="lfn-panel">
      <h2>Rascunhos pendentes</h2>
      <div class="lfn-table"><table>
        <thead><tr><th>ID</th><th>Prestador</th><th>Competência</th><th>Serviço</th><th>Valor</th><th>Estado</th></tr></thead>
        <tbody><tr v-for="draft in drafts" :key="draft.id"><td>{{draft.id}}</td>
          <td>{{companies.find(c=>c.id===draft.company_id)?.legal_name || (draft.company_id ? 'Empresa não disponível' : 'Rascunho legado sem vínculo') }}</td>
          <td>{{draft.competence}}</td><td>{{draft.description}}</td><td>{{money(draft.amount_minor)}}</td>
          <td>{{draft.status}} (sem validade fiscal)</td></tr>
        <tr v-if="!drafts.length"><td colspan="6">Nenhum rascunho.</td></tr>
        </tbody>
      </table></div>
      <button v-if="nextAfterId" type="button" @click="more">Carregar mais</button>
    </section>
  </main>
</template>
