<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class RouterAuditEvent
{
 public static function make(int $tenantId,int $routerId,int $actorId,string $action,array $details=[]):array{return['tenant_id'=>$tenantId,'router_id'=>$routerId,'actor_id'=>$actorId,'action'=>$action,'details'=>$details,'occurred_at'=>gmdate('c')];}
}
