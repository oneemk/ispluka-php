<?php

declare(strict_types=1);

namespace Ispluka\Core\Network;

use PDO;

final class PppoeActivityRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function markRouterSessionsOffline(int $tenantId, int $routerId): void
    {
        $s = $this->pdo->prepare(
            'UPDATE pppoe_activity_state
             SET online=FALSE, stale=FALSE, updated_at=CURRENT_TIMESTAMP
             WHERE tenant_id=:t AND router_id=:r'
        );
        $s->execute([':t' => $tenantId, ':r' => $routerId]);
    }

    public function upsert(PppoeActivityState $state): void
    {
        $s = $this->pdo->prepare(
            'INSERT INTO pppoe_activity_state
                (tenant_id,router_id,username,online,active_ip,last_seen_at,uptime_seconds,rx_bytes,tx_bytes,rx_rate_bps,tx_rate_bps,stale,updated_at)
             VALUES
                (:t,:r,:u,:o,:ip,:ls,:up,:rx,:tx,:rr,:tr,:st,CURRENT_TIMESTAMP)
             ON CONFLICT(tenant_id,router_id,username) DO UPDATE SET
                online=EXCLUDED.online,
                active_ip=EXCLUDED.active_ip,
                last_seen_at=EXCLUDED.last_seen_at,
                uptime_seconds=EXCLUDED.uptime_seconds,
                rx_bytes=EXCLUDED.rx_bytes,
                tx_bytes=EXCLUDED.tx_bytes,
                rx_rate_bps=EXCLUDED.rx_rate_bps,
                tx_rate_bps=EXCLUDED.tx_rate_bps,
                stale=EXCLUDED.stale,
                updated_at=CURRENT_TIMESTAMP'
        );

        // PostgreSQL boolean parameters must be bound as booleans. Passing
        // PHP false through PDO::execute(array) may become an empty string,
        // which PostgreSQL rejects for a boolean column.
        $s->bindValue(':t', $state->tenantId, PDO::PARAM_INT);
        $s->bindValue(':r', $state->routerId, PDO::PARAM_INT);
        $s->bindValue(':u', $state->username, PDO::PARAM_STR);
        $s->bindValue(':o', $state->online, PDO::PARAM_BOOL);
        $s->bindValue(':ip', $state->activeIp, $state->activeIp === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $s->bindValue(':ls', $state->lastSeenAt, $state->lastSeenAt === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $s->bindValue(':up', $state->uptimeSeconds, $state->uptimeSeconds === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $s->bindValue(':rx', $state->rxBytes, $state->rxBytes === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $s->bindValue(':tx', $state->txBytes, $state->txBytes === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $s->bindValue(':rr', $state->rxRateBps, $state->rxRateBps === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $s->bindValue(':tr', $state->txRateBps, $state->txRateBps === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $s->bindValue(':st', $state->stale, PDO::PARAM_BOOL);
        $s->execute();
    }

    public function find(int $tenantId, int $routerId, string $username): ?array
    {
        $s = $this->pdo->prepare(
            'SELECT username,online,active_ip,uptime_seconds,rx_bytes,tx_bytes,rx_rate_bps,tx_rate_bps,last_seen_at,updated_at,stale
             FROM pppoe_activity_state
             WHERE tenant_id=:t AND router_id=:r AND username=:u LIMIT 1'
        );
        $s->execute([':t' => $tenantId, ':r' => $routerId, ':u' => $username]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }
}
