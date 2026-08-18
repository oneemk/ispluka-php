<?php

declare(strict_types=1);

namespace Ispluka\Core\Hotspot;

use PDO;
use RuntimeException;

final class HotspotActionService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly MikroTikHotspotGateway $gateway,
    ) {}

    public function disconnect(int $tenantId, int $sessionId): void
    {
        $s = $this->pdo->prepare(
            "SELECT s.router_id,u.username
             FROM hotspot_sessions s
             JOIN hotspot_users u ON u.id=s.hotspot_user_id AND u.tenant_id=s.tenant_id
             WHERE s.tenant_id=:t AND s.id=:i AND s.status='active'"
        );
        $s->execute([':t' => $tenantId, ':i' => $sessionId]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        if (!$row || !$row['router_id']) {
            throw new RuntimeException('Active Hotspot session not found.');
        }

        $this->gateway->disconnect($tenantId, (int) $row['router_id'], (string) $row['username']);

        $u = $this->pdo->prepare(
            "UPDATE hotspot_sessions
             SET status='ended',ended_at=CURRENT_TIMESTAMP
             WHERE tenant_id=:t AND id=:i AND status='active'"
        );
        $u->execute([':t' => $tenantId, ':i' => $sessionId]);
    }

    public function syncRouterTime(int $tenantId, int $routerId, int $toleranceSeconds = 10): array
    {
        $router = $this->gateway->routerTime($tenantId, $routerId);
        return (new RouterTimeCheckService($this->pdo))->evaluate($tenantId, $routerId, $router, $toleranceSeconds);
    }

    public function activeUsers(int $tenantId, int $routerId): array
    {
        return $this->gateway->activeUsers($tenantId, $routerId);
    }

    public function createRouterUser(int $tenantId, int $routerId, array $attributes): void
    {
        $this->gateway->createUser($tenantId, $routerId, $attributes);
    }

    public function updateRouterUser(int $tenantId, int $routerId, string $username, array $attributes): void
    {
        $this->gateway->updateUser($tenantId, $routerId, $username, $attributes);
    }

    public function disableRouterUser(int $tenantId, int $routerId, string $username): void
    {
        $this->gateway->disableUser($tenantId, $routerId, $username);
    }

    public function enableRouterUser(int $tenantId, int $routerId, string $username): void
    {
        $this->gateway->enableUser($tenantId, $routerId, $username);
    }
}
