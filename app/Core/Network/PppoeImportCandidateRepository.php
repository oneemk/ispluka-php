<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use PDO;
final class PppoeImportCandidateRepository
{
 public function __construct(private readonly PDO $pdo){}
 public function upsert(PppoeImportCandidate $c):void{$q=$this->pdo->prepare("INSERT INTO pppoe_import_candidates(tenant_id,router_id,username,profile,active_ip,caller_id,mapped_customer_id,status,updated_at) VALUES(:t,:r,:u,:p,:ip,:cid,:c,:s,CURRENT_TIMESTAMP) ON CONFLICT(tenant_id,router_id,username) DO UPDATE SET profile=COALESCE(EXCLUDED.profile,pppoe_import_candidates.profile),active_ip=COALESCE(EXCLUDED.active_ip,pppoe_import_candidates.active_ip),caller_id=COALESCE(EXCLUDED.caller_id,pppoe_import_candidates.caller_id),updated_at=CURRENT_TIMESTAMP");$q->execute([':t'=>$c->tenantId,':r'=>$c->routerId,':u'=>$c->username,':p'=>$c->profile,':ip'=>$c->activeIp,':cid'=>$c->callerId,':c'=>$c->mappedCustomerId,':s'=>$c->status]);}
 public function pending(int $tenantId,int $limit=100):array{$limit=max(1,min(500,$limit));$q=$this->pdo->prepare('SELECT * FROM pppoe_import_candidates WHERE tenant_id=:t AND status=:s ORDER BY created_at ASC LIMIT '.$limit);$q->execute([':t'=>$tenantId,':s'=>'pending']);return$q->fetchAll(PDO::FETCH_ASSOC);}
}
