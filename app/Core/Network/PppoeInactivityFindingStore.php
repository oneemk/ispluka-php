<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;

use PDO;

final class PppoeInactivityFindingStore
{
    public function __construct(private readonly PDO $pdo) {}

    public function openOrRefresh(int $tenantId,int $routerId,string $username,string $message,array $details,int $now):void
    {
        $sql="INSERT INTO pppoe_reconciliation_findings(tenant_id,router_id,username,finding_type,severity,message,details,status,first_seen_at,last_seen_at)
              VALUES(:t,:r,:u,'inactive','warning',:m,:d,'open',TO_TIMESTAMP(:n),TO_TIMESTAMP(:n))
              ON CONFLICT DO NOTHING";
        $this->pdo->prepare($sql)->execute([':t'=>$tenantId,':r'=>$routerId,':u'=>$username,':m'=>$message,':d'=>json_encode($details,JSON_THROW_ON_ERROR),':n'=>$now]);
        $sql="UPDATE pppoe_reconciliation_findings SET status='open',message=:m,details=:d,last_seen_at=TO_TIMESTAMP(:n),resolved_at=NULL
              WHERE tenant_id=:t AND router_id=:r AND username=:u AND finding_type='inactive'";
        $this->pdo->prepare($sql)->execute([':m'=>$message,':d'=>json_encode($details,JSON_THROW_ON_ERROR),':n'=>$now,':t'=>$tenantId,':r'=>$routerId,':u'=>$username]);
    }

    public function resolve(int $tenantId,int $routerId,string $username,int $now):void
    {
        $sql="UPDATE pppoe_reconciliation_findings SET status='resolved',last_seen_at=TO_TIMESTAMP(:n),resolved_at=TO_TIMESTAMP(:n)
              WHERE tenant_id=:t AND router_id=:r AND username=:u AND finding_type='inactive' AND status='open'";
        $this->pdo->prepare($sql)->execute([':n'=>$now,':t'=>$tenantId,':r'=>$routerId,':u'=>$username]);
    }
}
