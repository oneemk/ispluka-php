<?php

declare(strict_types=1);
namespace Ispluka\Http\Controllers\Networking;

use PDO;

final class MikrotikAuditController
{
    public function __construct(private readonly PDO $pdo) {}
    public function index(int $tenantId,?string $status=null,?string $severity=null,?int $routerId=null):array
    {
        $where=['tenant_id=:tenant'];$params=[':tenant'=>$tenantId];
        if($status!==null&&in_array($status,['open','resolved'],true)){$where[]='status=:status';$params[':status']=$status;}
        if($severity!==null&&in_array($severity,['critical','high','warning','info'],true)){$where[]='severity=:severity';$params[':severity']=$severity;}
        if($routerId!==null&&$routerId>0){$where[]='router_id=:router';$params[':router']=$routerId;}
        $sql='SELECT id,router_id,username,finding_type,severity,message,details,status,first_seen_at,last_seen_at,resolved_at FROM pppoe_reconciliation_findings WHERE '.implode(' AND ',$where).' ORDER BY CASE severity WHEN \'critical\' THEN 1 WHEN \'high\' THEN 2 WHEN \'warning\' THEN 3 ELSE 4 END,last_seen_at DESC LIMIT 500';
        $q=$this->pdo->prepare($sql);$q->execute($params);return $q->fetchAll(PDO::FETCH_ASSOC);
    }
}
