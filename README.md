<!--
SPDX-FileCopyrightText: 2026 LibreCode Coop and contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->
# LibreFinance NFS-e Nacional

Nextcloud-native optional extension for Brazilian NFS-e draft preparation.

**0.2 is NOT an issuer.** The new optional `companyId` on a fiscal draft verifies an active legal company and matching issuer CNPJ in the same finance space, retaining compatibility with historic unlinked drafts. A linked draft is still not a fiscal authorization.

 It never contacts SEFIN/ADN, sends DPS, generates signed XML or fiscal credentials, nor creates an authorized NFS-e. This avoids fictitious fiscal documents and accidental production side effects. It stores *draft facts* with Nextcloud native authorization.

The next emission implementation must adapt the separately maintained AGPL PHP package `librecodeoop/nfse-php`, without copying code from Akaunting. Only actual SEFIN and ADN responses can transition fiscal statuses into authorized or cancelled; the signed XML must be authoritative and retained with audited versions in Nextcloud Files. Credentials must be provisioned through a dedicated vault with explicit operator authorization, scoped to taxpayer identity and environment. The module's domain must validate against the effective current official XSD, municipality-specific rules and legislated competence dates. No success can be claimed before production-restricted normative tests and a fiscal review.

Official NFS-e documentation: https://www.gov.br/nfse/pt-br/biblioteca/documentacao-tecnica/documentacao-atual/documentacao-atual and homologation: https://www.gov.br/nfse/pt-br/biblioteca/documentacao-tecnica/producao-restrita
