<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use PDO;
final class PppoeAuditQuery
{
 public function __construct(private readonly PDO $pdo){}
 public function inactive20Plus(int $tenantId,int $days=20,int $limit=500):array{$days=max(1,min(365,$days));$limit=max(1,min(5000,$limit));$q=$this->pdo->prepare("SELECT * FROM pppoe_activity_state WHERE tenant_id=:t AND online=false AND last_seen_at < CURRENT_TIMESTAMP - (:days * INTERVAL '1 day') ORDER BY last_seen_at ASC NULLS FIRST LIMIT ".$limit);$q->execute([':t'=>$tenantId,':days'=>$days]);return$q->fetchAll(PDO::FETCH_ASSOC);}
 public function usage(int $tenantId,int $routerId,string $username,string $from,string $to):array{$q=$this->pdo->prepare('SELECT bucket_start,rx_bytes,tx_bytes,online_seconds FROM pppoe_usage_hourly WHERE tenant_id=:t AND router_id=:r AND username=:u AND bucket_start>=:f AND bucket_start<:to ORDER BY bucket_start');$q->execute([':t'=>$tenantId,':r'=>$routerId,':u'=>$username,':f'=>$from,':to'=>$to]);return$q->fetchAll(PDO::FETCH_ASSOC);}
}
