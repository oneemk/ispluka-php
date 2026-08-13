<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use PDO;
final class PppoeEnforcementLogger
{
 public function __construct(private readonly PDO $pdo){}
 public function record(PppoeEnforcementOperation $op,string $status,?string $error=null,?int $actorId=null):void{$q=$this->pdo->prepare('INSERT INTO pppoe_enforcement_log(tenant_id,router_id,username,action,original_profile,target_profile,reason,status,error_message,actor_id) VALUES(:t,:r,:u,:a,:op,:tp,:rs,:s,:e,:actor)');$q->execute([':t'=>$op->tenantId,':r'=>$op->routerId,':u'=>$op->username,':a'=>$op->action,':op'=>$op->originalProfile,':tp'=>$op->targetProfile,':rs'=>$op->reason,':s'=>$status,':e'=>$error,':actor'=>$actorId]);}
}
