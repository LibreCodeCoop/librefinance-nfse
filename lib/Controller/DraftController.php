<?php
/** SPDX-FileCopyrightText: 2026 LibreCode Coop and contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later */
declare(strict_types=1);
namespace OCA\LibreFinanceNfse\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use DomainException;
use OCA\LibreFinanceNfse\Service\DraftService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
final class DraftController extends Controller {
    public function __construct(string $appName,IRequest $request,private DraftService $drafts){
        parent::__construct($appName,$request);
    }
    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'GET', url: '/api/spaces/{spaceId}/drafts')]
    public function index(int $spaceId,int $afterId=0):JSONResponse {
        return $this->respond(fn()=>$this->drafts->list($spaceId,$afterId));
    }
    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'POST', url: '/api/spaces/{spaceId}/drafts')]
    public function create(int $spaceId,string $operationKey,string $issuerCnpj,
        string $municipalityCode,string $competence,string $serviceCode,
        string $description,int $amountMinor,?int $companyId=null):JSONResponse {
        return $this->respond(fn()=>$this->drafts->create($spaceId,$operationKey,$issuerCnpj,
            $municipalityCode,$competence,$serviceCode,$description,$amountMinor,$companyId),201);
    }
    private function respond(callable $work,int $status=200):JSONResponse {
        try {
            $result=$work();
            if(is_array($result)&&($result['replayed']??false)===true){$status=200;}
            return new JSONResponse($result,$status);
        }catch(DomainException $e){
            $code=$e->getMessage();
            return new JSONResponse(['error'=>$code],match($code){
                'space_not_found','module_not_found','company_not_found'=>404,
                'permission_denied','module_disabled'=>403,
                'idempotency_conflict'=>409,
                'authentication_required'=>401,
                default=>422,
            });
        }
    }
}
