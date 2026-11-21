<?php
/** SPDX-FileCopyrightText: 2026 LibreCode Coop and contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later */
declare(strict_types=1);
\OCP\Util::addScript('librefinance_nfse','main');
\OCP\Util::addStyle('librefinance_nfse','main');
?>
<div id="librefinance-nfse-app" data-space-id="<?php p($_['spaceId']); ?>" data-embed="<?php p(!empty($_['embed'])?'1':'0'); ?>"></div>
