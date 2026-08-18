<?php

declare(strict_types=1);

namespace Ispluka\Core\Hotspot;

use PDO;
use RuntimeException;
use Throwable;

final class HotspotOperationalService
{
    private const RESOURCES = [
        'ip_binding' => [
            'print' => '/ip/hotspot/ip-binding/print',
            'add' => '/ip/hotspot/ip-binding/add',
            'remove' => '/ip/hotspot/ip-binding/remove',
            'fields' => ['address', 'mac-address', 'to-address', 'type', 'server', 'comment', 'disabled', 'bypassed'],
        ],
        'walled_garden' => [
            'print' => '/ip/hotspot/walled-garden/print',
            'add' => '/ip/hotspot/walled-garden/add',
            'remove' => '/ip/hotspot/walled-garden/remove',
            'fields' => ['dst-host', 'dst-path', 'action', 'comment', 'disabled'],
        ],
        'address_list' => [
            'print' => '/ip/firewall/address-list/print',
            'add' => '/ip/firewall/address-list/add',
            'remove' => '/ip/firewall/address-list/remove',
            'fields' => ['list', 'address', 'timeout', 'comment', 'disabled'],
        ],
    ];

    public function __construct(
        private readonly PDO $pdo,
        private readonly MikroTikHotspotGateway $gateway,
        private readonly HotspotAuditService $audit,
    ) {}

    public function listResource(int $tenantId, int $routerId, string $resource, ?int $actorUserId = null): array
    {
        $this->resource($resource);
        try {
            $rows = $this->gateway->resourceList($tenantId, $routerId, $resource);
            $this->audit->record($tenantId, 'resource.' . $resource . '.read', 'success', $routerId, null, $actorUserId, ['count' => count($rows)]);
            return $rows;
        } catch (Throwable $e) {
            $this->audit->record($tenantId, 'resource.' . $resource . '.read', 'failed', $routerId, null, $actorUserId, ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function createResource(int $tenantId, int $routerId, string $resource, array $attributes, ?int $actorUserId = null): array
    {
        $definition = $this->resource($resource);
        $payload = [];
        foreach ($definition['fields'] as $field) {
            if (array_key_exists($field, $attributes) && $attributes[$field] !== null && $attributes[$field] !== '') {
                $payload[$field] = $attributes[$field];
            }
        }
        if ($payload === []) {
            throw new RuntimeException('At least one valid MikroTik resource field is required.');
        }
        try {
            $result = $this->gateway->resourceCreate($tenantId, $routerId, $resource, $payload);
            $this->audit->record($tenantId, 'resource.' . $resource . '.create', 'success', $routerId, null, $actorUserId, ['payload' => $this->redact($payload), 'result' => $result]);
            return $result;
        } catch (Throwable $e) {
            $this->audit->record($tenantId, 'resource.' . $resource . '.create', 'failed', $routerId, null, $actorUserId, ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function deleteResource(int $tenantId, int $routerId, string $resource, string $id, ?int $actorUserId = null): void
    {
        $this->resource($resource);
        $id = trim($id);
        if ($id === '') {
            throw new RuntimeException('RouterOS resource ID is required.');
        }
        try {
            $this->gateway->resourceDelete($tenantId, $routerId, $resource, $id);
            $this->audit->record($tenantId, 'resource.' . $resource . '.delete', 'success', $routerId, null, $actorUserId, ['routeros_id' => $id]);
        } catch (Throwable $e) {
            $this->audit->record($tenantId, 'resource.' . $resource . '.delete', 'failed', $routerId, null, $actorUserId, ['routeros_id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function traffic(int $tenantId, int $routerId, ?int $actorUserId = null): array
    {
        try {
            $result = $this->gateway->traffic($tenantId, $routerId);
            $this->audit->record($tenantId, 'traffic.read', 'success', $routerId, null, $actorUserId, ['active_users' => $result['active_users'] ?? 0]);
            return $result;
        } catch (Throwable $e) {
            $this->audit->record($tenantId, 'traffic.read', 'failed', $routerId, null, $actorUserId, ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function loginHistory(int $tenantId, ?int $routerId = null, int $limit = 100): array
    {
        if ($tenantId < 1) {
            throw new RuntimeException('Invalid tenant context.');
        }
        $limit = max(1, min($limit, 500));
        $sql = 'SELECT s.id,s.router_id,s.hotspot_user_id,u.username,s.client_ip,s.mac_address,s.started_at,s.ended_at,s.bytes_in,s.bytes_out,s.status FROM hotspot_sessions s JOIN hotspot_users u ON u.id=s.hotspot_user_id AND u.tenant_id=s.tenant_id WHERE s.tenant_id=:t';
        $params = [':t' => $tenantId];
        if ($routerId !== null && $routerId > 0) {
            $sql .= ' AND s.router_id=:r';
            $params[':r'] = $routerId;
        }
        $sql .= ' ORDER BY s.started_at DESC LIMIT ' . $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function routerLogs(int $tenantId, int $routerId, ?int $actorUserId = null): array
    {
        try {
            $rows = $this->gateway->logs($tenantId, $routerId);
            $this->audit->record($tenantId, 'router.logs.read', 'success', $routerId, null, $actorUserId, ['count' => count($rows)]);
            return $rows;
        } catch (Throwable $e) {
            $this->audit->record($tenantId, 'router.logs.read', 'failed', $routerId, null, $actorUserId, ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function resource(string $resource): array
    {
        if (!isset(self::RESOURCES[$resource])) {
            throw new RuntimeException('Unsupported Hotspot operational resource.');
        }
        return self::RESOURCES[$resource];
    }

    private function redact(array $payload): array
    {
        return $payload;
    }
}
