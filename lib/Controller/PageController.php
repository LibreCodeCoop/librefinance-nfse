<?php
/**
 * SPDX-FileCopyrightText: 2026 LibreCode Coop and contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
declare(strict_types=1);

namespace OCA\LibreFinanceNfse\Controller;

use DomainException;
use OCA\LibreFinance\PublicAPI\FinanceApi;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;
use OCP\IURLGenerator;

/**
 * Preserve legacy module links but render only ONE canonical Nextcloud UI.
 * No redirects to user-supplied URLs and no iframe or copied form state.
 */
final class PageController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private FinanceApi $finance,
        private IURLGenerator $urls,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function index(int $spaceId, bool $embed = false): Response {
        try {
            $this->finance->requireModule($spaceId, 'librefinance_nfse', 'viewer');
        } catch (DomainException) {
            return new NotFoundResponse();
        }
        $base = $this->urls->linkToRoute('librefinance.page.index');
        return new RedirectResponse($base . '?spaceId=' . $spaceId . '&view=nfse');
    }
}
