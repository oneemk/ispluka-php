<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use PDO;
final class PppoeActivityRepository
{
 public function __construct(private readonly PDO $pdo){}
 public function upsert(PppoeActivityState $state):void{$s=$this->pdo->prepare("INSERT INTO pppoe_activity_state(tenant_id,router_id,username,online,active_ip,last_seen_at,uptime_seconds,rx_bytes,tx_bytes,rx_rate_bps,tx_rate_bps,stale,updated_at) VALUES(:t,:r,:u,:o,:ip,:ls,:up,:rx,:tx,:rr,:tr,:st,CURRENT_TIMESTAMP) ON CONFLICT(tenant_id,router_id,username) DO UPDATE SET online=EXCLUDED.online,active_ip=EXCLUDED.active_ip,last_seen_at=EXCLUDED.last_seen_at,uptime_seconds=EXCLUDED.uptime_seconds,rx_bytes=EXCLUDED.rx_bytes,tx_bytes=EXCLUDED.tx_bytes,rx_rate_bps=EXCLUDED.rx_rate_bps,tx_rate_bps=EXCLUDED.tx_rate_bps,stale=EXCLUDED.stale,updated_at=CURRENT_TIMESTAMP");$s->execute([':t'=>$state->tenantId,':r'=>$state->routerId,':u'=>$state->username,':o'=>$state->online,':ip'=>$state->activeIp,':ls'=>$state->lastSeenAt,':up'=>$state->uptimeSeconds,':rx'=>$state->rxBytes,':tx'=>$state->txBytes,':rr'=>$state->rxRateBps,':tr'=>$state->txRateBps,':st'=>$state->stale]);}
}
