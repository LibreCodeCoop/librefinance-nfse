<?php
/**
 * SPDX-FileCopyrightText: 2026 LibreCode Coop and contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
declare(strict_types=1);
require_once dirname(__DIR__,2).'/librefinance/tests/integration/TestHarness.php';
use LibreFinance\Tests\Integration\TestHarness;
$t=new TestHarness();
$a='lfmaintestA';$b='lfmaintestB';$app='librefinance_nfse';
$tag=bin2hex(random_bytes(6));
$s=$t->check('create fiscal staging space',$a,'POST','/spaces',
    ['name'=>'Synthetic fiscal draft staging','kind'=>'organization','currency'=>'BRL'],201);
$id=(int)$s['id'];$base='/spaces/'.$id;
$cnpj='999999990001';
foreach([[5,4,3,2,9,8,7,6,5,4,3,2],[6,5,4,3,2,9,8,7,6,5,4,3,2]] as $weights){
    $sum=0;
    foreach($weights as $i=>$weight){$sum+=(int)$cnpj[$i]*$weight;}
    $r=$sum%11;$cnpj.=(string)($r<2?0:11-$r);
}
$draft=['operationKey'=>'nfse:test:'.$tag,'issuerCnpj'=>$cnpj,
    'municipalityCode'=>'3304557','competence'=>'2026-11-20',
    'serviceCode'=>'01.01','description'=>'Synthetic development-only work',
    'amountMinor'=>10000];
$t->check('discover native NFS-e draft extension',$a,'GET',$base.'/modules',null,200,
    static fn(array $v):bool=>count(array_filter($v['modules']??[],
        static fn(array $m):bool=>$m['id']==='librefinance_nfse'))===1);
$t->checkModule('draft list initially empty',$a,$app,'GET',$base.'/drafts',null,200,
    static fn(array $v):bool=>($v['drafts']??null)===[] && ($v['emissionAvailable']??true)===false);
$t->checkModule('create NFS-e staging record but not fiscal document',$a,$app,'POST',
    $base.'/drafts',$draft,201,
    static fn(array $v):bool=>($v['status']??null)==='draft'
        && ($v['fiscalDocument']??true)===false);
$entity=$t->check('create source-scoped legal issuer',$a,'POST',
    $base.'/companies',['legalName'=>'Synthetic Fiscal Issuer',
        'country'=>'BR','taxId'=>$cnpj],201);
$linked=array_replace($draft,['operationKey'=>'nfse:linked:'.$tag,
    'companyId'=>(int)$entity['id']]);
$t->checkModule('linked NFS-e draft verifies legal entity identity',$a,$app,'POST',
    $base.'/drafts',$linked,201,
    static fn(array $v):bool=>($v['companyId']??null)===(int)$entity['id']);
$t->checkModule('linked fiscal draft retains company reference',$a,$app,'GET',
    $base.'/drafts',null,200,
    static fn(array $v):bool=>count(array_filter($v['drafts']??[],
        static fn(array $row):bool=>($row['company_id']??null)===(int)$entity['id']))===1);
$t->checkModule('unknown legal issuer refused',$a,$app,'POST',
    $base.'/drafts',array_replace($linked,['operationKey'=>'nfse:unknown:'.$tag,
        'companyId'=>99999999]),404);
$t->checkModule('issuer CNPJ cannot differ from registered company',$a,$app,'POST',
    $base.'/drafts',array_replace($linked,['operationKey'=>'nfse:mismatch:'.$tag,
        'issuerCnpj'=>'11222333000181']),422,
    static fn(array $v):bool=>($v['error']??null)==='issuer_company_mismatch');
$t->checkModule('idempotent NFS-e draft replay',$a,$app,'POST',$base.'/drafts',$draft,200,
    static fn(array $v):bool=>($v['replayed']??null)===true);
$t->checkModule('conflicting draft replay denied',$a,$app,'POST',
    $base.'/drafts',array_replace($draft,['amountMinor'=>12000]),409);
$t->checkModule('invalid CNPJ denied',$a,$app,'POST',
    $base.'/drafts',array_replace($draft,['operationKey'=>'nfse:invalid:'.$tag,
        'issuerCnpj'=>'00000000000000']),422);
$t->checkModule('invalid IBGE code denied',$a,$app,'POST',
    $base.'/drafts',array_replace($draft,['operationKey'=>'nfse:ibge:'.$tag,
        'municipalityCode'=>'0000000']),422);
$t->checkModule('invalid competence denied',$a,$app,'POST',
    $base.'/drafts',array_replace($draft,['operationKey'=>'nfse:date:'.$tag,
        'competence'=>'2026-02-30']),422);
$t->checkModule('unauthorized space cannot read fiscal staging',$b,$app,'GET',$base.'/drafts',null,404);
$t->checkModule('unauthorized space cannot create fiscal staging',$b,$app,'POST',
    $base.'/drafts',array_replace($draft,['operationKey'=>'nfse:outside:'.$tag]),404);
$t->check('disable NFS-e staging per space',$a,'PUT',$base.'/modules/librefinance_nfse',
    ['enabled'=>false],200);
$t->checkModule('disabled staging denies access',$a,$app,'GET',$base.'/drafts',null,403);
$t->check('reactivate staging',$a,'PUT',$base.'/modules/librefinance_nfse',['enabled'=>true],200);
$t->check('grant fiscal viewer',$a,'POST',$base.'/members',['uid'=>$b,'role'=>'viewer'],200);
$t->checkModule('fiscal viewer can inspect drafts',$b,$app,'GET',$base.'/drafts',null,200);
$t->checkModule('fiscal viewer cannot create drafts',$b,$app,'POST',
    $base.'/drafts',array_replace($draft,['operationKey'=>'nfse:viewer:'.$tag]),403);
$t->check('revoke fiscal viewer',$a,'DELETE',$base.'/members/'.$b,null,200);
$t->checkModule('revoked fiscal viewer denied',$b,$app,'GET',$base.'/drafts',null,404);
$t->finish();
