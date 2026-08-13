<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class PppoeImportAudit
{
 public function event(PppoeImportCandidate $candidate,string $action,?int $actorId=null):array{return['tenant_id'=>$candidate->tenantId,'router_id'=>$candidate->routerId,'username'=>$candidate->username,'action'=>$action,'mapped_customer_id'=>$candidate->mappedCustomerId,'actor_id'=>$actorId,'occurred_at'=>gmdate('c')];}
}
