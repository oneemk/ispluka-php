<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use PDO;
use DateTimeImmutable;
final class PppoeUsageAggregator
{
 public function __construct(private readonly PDO $pdo){}
 public function add(int $tenantId,int $routerId,string $username,PppoeUsageDelta $delta,DateTimeImmutable $at,int $onlineSeconds=0):void{$bucket=PppoeUsageBucket::hour($at);$q=$this->pdo->prepare("INSERT INTO pppoe_usage_hourly(tenant_id,router_id,username,bucket_start,rx_bytes,tx_bytes,online_seconds,samples) VALUES(:t,:r,:u,:b,:rx,:tx,:on,1) ON CONFLICT(tenant_id,router_id,username,bucket_start) DO UPDATE SET rx_bytes=pppoe_usage_hourly.rx_bytes+EXCLUDED.rx_bytes,tx_bytes=pppoe_usage_hourly.tx_bytes+EXCLUDED.tx_bytes,online_seconds=pppoe_usage_hourly.online_seconds+EXCLUDED.online_seconds,samples=pppoe_usage_hourly.samples+1");$q->execute([':t'=>$tenantId,':r'=>$routerId,':u'=>$username,':b'=>$bucket,':rx'=>$delta->rxBytes,':tx'=>$delta->txBytes,':on'=>max(0,$onlineSeconds)]);}
}
