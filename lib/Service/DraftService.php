<?php
/**
 * SPDX-FileCopyrightText: 2026 LibreCode Coop and contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
declare(strict_types=1);
namespace OCA\LibreFinanceNfse\Service;

use DomainException;
use OCA\LibreFinance\PublicAPI\FinanceApi;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUserSession;

final class DraftService {
    public const MODULE='librefinance_nfse';
    public function __construct(private FinanceApi $finance,private IDBConnection $db,
        private IUserSession $session) {}

    public function list(int $spaceId,int $afterId=0):array {
        $this->finance->requireModule($spaceId,self::MODULE);
        if($afterId<0){throw new DomainException('invalid_cursor');}
        $q=$this->db->getQueryBuilder();
        $q->select('id','operation_key','issuer_cnpj','municipality_code','competence','service_code',
            'description','amount_minor','status','created_at','company_id')
            ->from('lfn_drafts')
            ->where($q->expr()->eq('space_id',$q->createNamedParameter($spaceId,IQueryBuilder::PARAM_INT)))
            ->andWhere($q->expr()->gt('id',$q->createNamedParameter($afterId,IQueryBuilder::PARAM_INT)))
            ->orderBy('id','ASC')->setMaxResults(51);
        $rows=$q->executeQuery()->fetchAllAssociative();
        $more=count($rows)>50;$rows=array_slice($rows,0,50);
        foreach($rows as &$row){
            foreach(['id','amount_minor','created_at'] as $key){$row[$key]=(int)$row[$key];}
            $row['company_id']=$row['company_id']===null?null:(int)$row['company_id'];
        }
        unset($row);
        return ['drafts'=>$rows,'nextAfterId'=>$more?(int)end($rows)['id']:null,
            'emissionAvailable'=>false];
    }

    public function create(int $spaceId,string $operationKey,string $issuerCnpj,
        string $municipalityCode,string $competence,string $serviceCode,
        string $description,int $amountMinor,?int $companyId=null):array {
        $this->finance->requireModule($spaceId,self::MODULE,'operator');
        if(!preg_match('/^[A-Za-z0-9_.:-]{12,64}$/',$operationKey)){
            throw new DomainException('invalid_idempotency_key');
        }
        $issuerCnpj=preg_replace('/\D+/','',$issuerCnpj);
        if(!self::validCnpj($issuerCnpj)){
            throw new DomainException('invalid_cnpj');
        }
        if(!preg_match('/^[1-9][0-9]{6}$/',$municipalityCode)){
            throw new DomainException('invalid_municipality_code');
        }
        $parsed=\DateTimeImmutable::createFromFormat('!Y-m-d',$competence);
        if(!$parsed||$parsed->format('Y-m-d')!==$competence){
            throw new DomainException('invalid_competence');
        }
        $serviceCode=trim($serviceCode);
        if(!preg_match('/^[0-9.]{1,15}$/',$serviceCode)){
            throw new DomainException('invalid_service_code');
        }
        $description=trim($description);
        if($description===''||mb_strlen($description)>255){
            throw new DomainException('invalid_description');
        }
        if($amountMinor<1||$amountMinor>9000000000000){
            throw new DomainException('invalid_amount');
        }
        if ($companyId !== null) {
            $this->finance->requireCompanyCnpj($spaceId,self::MODULE,$companyId,$issuerCnpj);
        }
        // Keep the legacy hash exactly unchanged for unlinked historic drafts.
        $identity=[$issuerCnpj,$municipalityCode,$competence,$serviceCode,$description,$amountMinor];
        if ($companyId !== null) {$identity[]=$companyId;}
        $hash=hash('sha256',json_encode($identity,JSON_THROW_ON_ERROR));
        $this->db->beginTransaction();
        try{
            $previous=$this->findByKey($spaceId,$operationKey);
            if($previous){
                if($previous['request_hash']!==$hash){throw new DomainException('idempotency_conflict');}
                $this->db->commit();
                return ['id'=>(int)$previous['id'],'status'=>'draft','replayed'=>true,'fiscalDocument'=>false];
            }
            $this->insert('lfn_drafts',[
                'space_id'=>$spaceId,'operation_key'=>$operationKey,'request_hash'=>$hash,
                'issuer_cnpj'=>$issuerCnpj,'municipality_code'=>$municipalityCode,
                'competence'=>$competence,'service_code'=>$serviceCode,
                'description'=>$description,'amount_minor'=>$amountMinor,
                'status'=>'draft','company_id'=>$companyId,'created_by'=>$this->uid(),'created_at'=>time(),
            ]);
            $id=(int)$this->db->lastInsertId('*PREFIX*lfn_drafts');
            $this->db->commit();
            return ['id'=>$id,'status'=>'draft','companyId'=>$companyId,'replayed'=>false,'fiscalDocument'=>false];
        }catch(\Throwable $e){
            $this->db->rollBack();
            if($e instanceof \OCP\DB\Exception &&
                $e->getReason()===\OCP\DB\Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
                $previous=$this->findByKey($spaceId,$operationKey);
                if($previous && $previous['request_hash']===$hash){
                    return ['id'=>(int)$previous['id'],'status'=>'draft','replayed'=>true,'fiscalDocument'=>false];
                }
                throw new DomainException('idempotency_conflict',previous:$e);
            }
            throw $e;
        }
    }

    private function findByKey(int $spaceId,string $key):?array {
        $q=$this->db->getQueryBuilder();
        $q->select('id','request_hash')->from('lfn_drafts')
            ->where($q->expr()->eq('space_id',$q->createNamedParameter($spaceId,IQueryBuilder::PARAM_INT)))
            ->andWhere($q->expr()->eq('operation_key',$q->createNamedParameter($key)));
        return $q->executeQuery()->fetchAssociative()?:null;
    }
    /** @param array<string,int|string> $values */
    private function insert(string $table,array $values):void {
        $q=$this->db->getQueryBuilder();$params=[];
        foreach($values as $field=>$value){
            $params[$field]=$q->createNamedParameter($value,is_int($value)?IQueryBuilder::PARAM_INT:IQueryBuilder::PARAM_STR);
        }
        $q->insert($table)->values($params)->executeStatement();
    }
    private function uid():string {
        $uid=$this->session->getUser()?->getUID();
        if(!$uid){throw new DomainException('authentication_required');}
        return $uid;
    }

    public static function validCnpj(string $digits):bool {
        if(!preg_match('/^[0-9]{14}$/',$digits)||preg_match('/^(.)\1{13}$/',$digits)){return false;}
        $prefix=substr($digits,0,12);
        foreach([[5,4,3,2,9,8,7,6,5,4,3,2],[6,5,4,3,2,9,8,7,6,5,4,3,2]] as $weights){
            $sum=0;
            foreach($weights as $index=>$weight){$sum+=(int)$prefix[$index]*$weight;}
            $remainder=$sum%11;
            $prefix.=(string)($remainder<2?0:11-$remainder);
        }
        return $prefix===$digits;
    }
}
