<?php

declare(strict_types=1);

namespace Ispluka\Core\Hotspot;

use RuntimeException;

interface MikroTikHotspotGateway
{
    public function routerTime(int $tenantId, int $routerId): \DateTimeImmutable;
    public function activeUsers(int $tenantId, int $routerId): array;
    public function disconnect(int $tenantId, int $routerId, string $username): void;
    public function createUser(int $tenantId, int $routerId, array $attributes): void;
    public function updateUser(int $tenantId, int $routerId, string $username, array $attributes): void;
    public function disableUser(int $tenantId, int $routerId, string $username): void;
    public function enableUser(int $tenantId, int $routerId, string $username): void;
    public function resourceList(int $tenantId, int $routerId, string $resource): array;
    public function resourceCreate(int $tenantId, int $routerId, string $resource, array $attributes): array;
    public function resourceDelete(int $tenantId, int $routerId, string $resource, string $id): void;
    public function traffic(int $tenantId, int $routerId): array;
    public function logs(int $tenantId, int $routerId): array;
}

final class UnsupportedMikroTikHotspotGateway implements MikroTikHotspotGateway
{
    private function unavailable(): never
    {
        throw new RuntimeException('MikroTik Hotspot gateway is not configured.');
    }

    public function routerTime(int $tenantId, int $routerId): \DateTimeImmutable { $this->unavailable(); }
    public function activeUsers(int $tenantId, int $routerId): array { $this->unavailable(); }
    public function disconnect(int $tenantId, int $routerId, string $username): void { $this->unavailable(); }
    public function createUser(int $tenantId, int $routerId, array $attributes): void { $this->unavailable(); }
    public function updateUser(int $tenantId, int $routerId, string $username, array $attributes): void { $this->unavailable(); }
    public function disableUser(int $tenantId, int $routerId, string $username): void { $this->unavailable(); }
    public function enableUser(int $tenantId, int $routerId, string $username): void { $this->unavailable(); }
    public function resourceList(int $tenantId, int $routerId, string $resource): array { $this->unavailable(); }
    public function resourceCreate(int $tenantId, int $routerId, string $resource, array $attributes): array { $this->unavailable(); }
    public function resourceDelete(int $tenantId, int $routerId, string $resource, string $id): void { $this->unavailable(); }
    public function traffic(int $tenantId, int $routerId): array { $this->unavailable(); }
    public function logs(int $tenantId, int $routerId): array { $this->unavailable(); }
}
