<?php

declare(strict_types=1);

namespace Ispluka\Core\Hotspot;

use PDO;
use RuntimeException;
use Throwable;

final class HotspotActionService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly MikroTikHotspotGateway $gateway,
        private readonly HotspotAuditService $audit,
    ) {}

    public function disconnect(int $tenantId, int $sessionId, ?int $actorUserId = null): void
    {
        $s = $this->pdo->prepare(
            "SELECT s.router_id,s.hotspot_user_id,u.username
             FROM hotspot_sessions s
             JOIN hotspot_users u ON u.id=s.hotspot_user_id AND u.tenant_id=s.tenant_id
             WHERE s.tenant_id=:t AND s.id=:i AND s.status='active'"
        );
        $s->execute([':t' => $tenantId, ':i' => $sessionId]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        if (!$row || !$row['router_id']) {
            throw new RuntimeException('Active Hotspot session not found.');
        }

        try {
            $this->gateway->disconnect($tenantId, (int) $row['router_id'], (string) $row['username']);
            $u = $this->pdo->prepare(
                "UPDATE hotspot_sessions
                 SET status='ended',ended_at=CURRENT_TIMESTAMP
                 WHERE tenant_id=:t AND id=:i AND status='active'"
            );
            $u->execute([':t' => $tenantId, ':i' => $sessionId]);
            $this->audit->record($tenantId, 'session.disconnect', 'success', (int) $row['router_id'], (int) $row['hotspot_user_id'], $actorUserId, ['session_id' => $sessionId, 'username' => $row['username']]);
        } catch (Throwable $e) {
            $this->audit->record($tenantId, 'session.disconnect', 'failed', (int) $row['router_id'], (int) $row['hotspot_user_id'], $actorUserId, ['session_id' => $sessionId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function syncRouterTime(int $tenantId, int $routerId, int $toleranceSeconds = 10, ?int $actorUserId = null): array
    {
        try {
            $router = $this->gateway->routerTime($tenantId, $routerId);
            $result = (new RouterTimeCheckService($this->pdo))->evaluate($tenantId, $routerId, $router, $toleranceSeconds);
            $this->audit->record($tenantId, 'router.time_check', 'success', $routerId, null, $actorUserId, $result);
            return $result;
        } catch (Throwable $e) {
            $this->audit->record($tenantId, 'router.time_check', 'failed', $routerId, null, $actorUserId, ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function activeUsers(int $tenantId, int $routerId, ?int $actorUserId = null): array
    {
        try {
            $rows = $this->gateway->activeUsers($tenantId, $routerId);
            $this->audit->record($tenantId, 'router.active_users.read', 'success', $routerId, null, $actorUserId, ['count' => count($rows)]);
            return $rows;
        } catch (Throwable $e) {
            $this->audit->record($tenantId, 'router.active_users.read', 'failed', $routerId, null, $actorUserId, ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function createRouterUser(int $tenantId, int $routerId, array $attributes, ?int $actorUserId = null): void
    {
        try {
            $this->gateway->createUser($tenantId, $routerId, $attributes);
            $this->audit->record($tenantId, 'user.create', 'success', $routerId, null, $actorUserId, ['username' => $attributes['name'] ?? null]);
        } catch (Throwable $e) {
            $this->audit->record($tenantId, 'user.create', 'failed', $routerId, null, $actorUserId, ['username' => $attributes['name'] ?? null, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function updateRouterUser(int $tenantId, int $routerId, string $username, array $attributes, ?int $actorUserId = null): void
    {
        try {
            $this->gateway->updateUser($tenantId, $routerId, $username, $attributes);
            $this->audit->record($tenantId, 'user.update', 'success', $routerId, null, $actorUserId, ['username' => $username]);
        } catch (Throwable $e) {
            $this->audit->record($tenantId, 'user.update', 'failed', $routerId, null, $actorUserId, ['username' => $username, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function disableRouterUser(int $tenantId, int $routerId, string $username, ?int $actorUserId = null): void
    {
        $this->toggleRouterUser($tenantId, $routerId, $username, false, $actorUserId);
    }

    public function enableRouterUser(int $tenantId, int $routerId, string $username, ?int $actorUserId = null): void
    {
        $this->toggleRouterUser($tenantId, $routerId, $username, true, $actorUserId);
    }

    private function toggleRouterUser(int $tenantId, int $routerId, string $username, bool $enable, ?int $actorUserId): void
    {
        $action = $enable ? 'user.enable' : 'user.disable';
        try {
            if ($enable) {
                $this->gateway->enableUser($tenantId, $routerId, $username);
            } else {
                $this->gateway->disableUser($tenantId, $routerId, $username);
            }
            $this->audit->record($tenantId, $action, 'success', $routerId, null, $actorUserId, ['username' => $username]);
        } catch (Throwable $e) {
            $this->audit->record($tenantId, $action, 'failed', $routerId, null, $actorUserId, ['username' => $username, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
