<?php

declare(strict_types=1);
namespace Ispluka\Core\Hotspot;
use RuntimeException;
interface MikroTikHotspotGateway
{
    public function routerTime(int $routerId): \DateTimeImmutable;
    public function activeUsers(int $routerId): array;
    public function disconnect(int $routerId,string $username): void;
    public function createUser(int $routerId,array $attributes): void;
    public function updateUser(int $routerId,string $username,array $attributes): void;
    public function disableUser(int $routerId,string $username): void;
    public function enableUser(int $routerId,string $username): void;
}
final class UnsupportedMikroTikHotspotGateway implements MikroTikHotspotGateway
{
    private function unavailable(): never { throw new RuntimeException('MikroTik Hotspot gateway is not configured.'); }
    public function routerTime(int $routerId): \DateTimeImmutable { $this->unavailable(); }
    public function activeUsers(int $routerId): array { $this->unavailable(); }
    public function disconnect(int $routerId,string $username): void { $this->unavailable(); }
    public function createUser(int $routerId,array $attributes): void { $this->unavailable(); }
    public function updateUser(int $routerId,string $username,array $attributes): void { $this->unavailable(); }
    public function disableUser(int $routerId,string $username): void { $this->unavailable(); }
    public function enableUser(int $routerId,string $username): void { $this->unavailable(); }
}
