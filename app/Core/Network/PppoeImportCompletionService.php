<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use PDO;
final class PppoeImportCompletionService
{
 public function __construct(private readonly PDO $pdo,private readonly PppoeImportCompletionValidator $validator){}
 public function complete(PppoeImportCandidate $candidate,int $customerId,?int $actorId=null):void{$this->validator->validate($candidate,$customerId);$this->pdo->beginTransaction();try{$q=$this->pdo->prepare("UPDATE pppoe_import_candidates SET mapped_customer_id=:c,status='completed',completed_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE tenant_id=:t AND router_id=:r AND username=:u AND status='pending'");$q->execute([':c'=>$customerId,':t'=>$candidate->tenantId,':r'=>$candidate->routerId,':u'=>$candidate->username]);if($q->rowCount()!==1)throw new \RuntimeException('Candidate changed or was already completed.');$a=$this->pdo->prepare('INSERT INTO pppoe_import_audit(tenant_id,router_id,username,action,mapped_customer_id,actor_id) VALUES(:t,:r,:u,:a,:c,:actor)');$a->execute([':t'=>$candidate->tenantId,':r'=>$candidate->routerId,':u'=>$candidate->username,':a'=>'mapped_to_customer',':c'=>$customerId,':actor'=>$actorId]);$this->pdo->commit();}catch(\Throwable $e){$this->pdo->rollBack();throw$e;}}
}
