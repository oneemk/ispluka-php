<?php

declare(strict_types=1);

namespace Ispluka\Core\Hotspot;

use DateTimeImmutable;
use DateTimeZone;
use Ispluka\Core\Network\MikrotikClientInterface;
use Ispluka\Core\Security\SecretBox;
use Ispluka\Repositories\RouterRepository;
use RuntimeException;

final class RouterOsHotspotGateway implements MikroTikHotspotGateway
{
    public function __construct(
        private readonly RouterRepository $routers,
        private readonly SecretBox $secrets,
        private readonly MikrotikClientInterface $client,
    ) {}

    public function routerTime(int $tenantId, int $routerId): DateTimeImmutable
    {
        $router = $this->router($tenantId, $routerId);
        try {
            $this->connect($router);
            $row = $this->client->command('/system/clock/print')[0] ?? [];
            $date = trim((string) ($row['date'] ?? ''));
            $time = trim((string) ($row['time'] ?? ''));
            if ($date === '' || $time === '') {
                throw new RuntimeException('RouterOS did not return a usable clock value.');
            }
            $value = new DateTimeImmutable($date . ' ' . $time, $this->routerTimezone($row));
            $this->routers->markConnection($tenantId, $routerId, true);
            return $value;
        } catch (\Throwable $e) {
            $this->routers->markConnection($tenantId, $routerId, false, $e->getMessage());
            throw new RuntimeException('Unable to read MikroTik router time.', 0, $e);
        } finally {
            $this->transportDisconnect();
        }
    }

    public function activeUsers(int $tenantId, int $routerId): array
    {
        $router = $this->router($tenantId, $routerId);
        try {
            $this->connect($router);
            $rows = $this->client->command('/ip/hotspot/active/print');
            $this->routers->markConnection($tenantId, $routerId, true);
            return array_map(static fn (array $row): array => [
                'id' => $row['.id'] ?? null,
                'username' => $row['user'] ?? $row['name'] ?? null,
                'address' => $row['address'] ?? null,
                'mac_address' => $row['mac-address'] ?? $row['mac_address'] ?? null,
                'uptime' => $row['uptime'] ?? null,
                'bytes_in' => $row['bytes-in'] ?? $row['bytes_in'] ?? null,
                'bytes_out' => $row['bytes-out'] ?? $row['bytes_out'] ?? null,
                'server' => $row['server'] ?? null,
                'session_id' => $row['session-id'] ?? $row['session_id'] ?? null,
            ], $rows);
        } catch (\Throwable $e) {
            $this->routers->markConnection($tenantId, $routerId, false, $e->getMessage());
            throw new RuntimeException('Unable to read active MikroTik Hotspot sessions.', 0, $e);
        } finally {
            $this->transportDisconnect();
        }
    }

    public function disconnect(int $tenantId, int $routerId, string $username): void
    {
        $username = trim($username);
        if ($username === '') {
            throw new RuntimeException('Hotspot username is required.');
        }
        $router = $this->router($tenantId, $routerId);
        try {
            $this->connect($router);
            $rows = $this->client->command('/ip/hotspot/active/print', ['?user' => $username]);
            foreach ($rows as $row) {
                $id = $row['.id'] ?? null;
                if ($id !== null && $id !== '') {
                    $this->client->command('/ip/hotspot/active/remove', ['.id' => $id]);
                }
            }
            $this->routers->markConnection($tenantId, $routerId, true);
        } catch (\Throwable $e) {
            $this->routers->markConnection($tenantId, $routerId, false, $e->getMessage());
            throw new RuntimeException('Unable to disconnect MikroTik Hotspot user.', 0, $e);
        } finally {
            $this->transportDisconnect();
        }
    }

    public function createUser(int $tenantId, int $routerId, array $attributes): void
    {
        $router = $this->router($tenantId, $routerId);
        $payload = $this->userPayload($attributes);
        try {
            $this->connect($router);
            $existing = $this->client->command('/ip/hotspot/user/print', ['?name' => (string) $payload['name']]);
            if ($existing !== []) {
                throw new RuntimeException('Hotspot user already exists on the router.');
            }
            $this->client->command('/ip/hotspot/user/add', $payload);
            $this->routers->markConnection($tenantId, $routerId, true);
        } catch (\Throwable $e) {
            $this->routers->markConnection($tenantId, $routerId, false, $e->getMessage());
            throw new RuntimeException('Unable to create MikroTik Hotspot user.', 0, $e);
        } finally {
            $this->transportDisconnect();
        }
    }

    public function updateUser(int $tenantId, int $routerId, string $username, array $attributes): void
    {
        $router = $this->router($tenantId, $routerId);
        try {
            $this->connect($router);
            $rows = $this->client->command('/ip/hotspot/user/print', ['?name' => trim($username)]);
            $id = $rows[0]['.id'] ?? null;
            if ($id === null || $id === '') {
                throw new RuntimeException('Hotspot user not found on the router.');
            }
            $this->client->command('/ip/hotspot/user/set', ['.id' => $id] + $this->userPayload($attributes, false));
            $this->routers->markConnection($tenantId, $routerId, true);
        } catch (\Throwable $e) {
            $this->routers->markConnection($tenantId, $routerId, false, $e->getMessage());
            throw new RuntimeException('Unable to update MikroTik Hotspot user.', 0, $e);
        } finally {
            $this->transportDisconnect();
        }
    }

    public function disableUser(int $tenantId, int $routerId, string $username): void
    {
        $this->toggle($tenantId, $routerId, $username, '/ip/hotspot/user/disable');
    }

    public function enableUser(int $tenantId, int $routerId, string $username): void
    {
        $this->toggle($tenantId, $routerId, $username, '/ip/hotspot/user/enable');
    }

    private function toggle(int $tenantId, int $routerId, string $username, string $command): void
    {
        $router = $this->router($tenantId, $routerId);
        try {
            $this->connect($router);
            $rows = $this->client->command('/ip/hotspot/user/print', ['?name' => trim($username)]);
            $id = $rows[0]['.id'] ?? null;
            if ($id === null || $id === '') {
                throw new RuntimeException('Hotspot user not found on the router.');
            }
            $this->client->command($command, ['.id' => $id]);
            $this->routers->markConnection($tenantId, $routerId, true);
        } catch (\Throwable $e) {
            $this->routers->markConnection($tenantId, $routerId, false, $e->getMessage());
            throw new RuntimeException('Unable to change MikroTik Hotspot user state.', 0, $e);
        } finally {
            $this->transportDisconnect();
        }
    }

    private function router(int $tenantId, int $routerId): array
    {
        if ($tenantId < 1 || $routerId < 1) {
            throw new RuntimeException('Valid tenant and router context are required.');
        }
        $router = $this->routers->find($tenantId, $routerId);
        if ($router === null) {
            throw new RuntimeException('Router not found.');
        }
        return $router;
    }

    private function connect(array $router): void
    {
        $method = strtolower((string) ($router['connection_method'] ?? 'api'));
        $config = [
            'host' => (string) $router['host'],
            'connection_method' => $method,
            'username' => (string) $router['username'],
            'password' => $this->secrets->decrypt((string) $router['encrypted_password']),
        ];
        if ($method === 'ssh') {
            $config['ssh_port'] = (int) ($router['ssh_port'] ?? 22);
        } else {
            $config['api_port'] = (int) ($router['api_port'] ?? 8728);
            $config['verify_ssl'] = (bool) ($router['verify_ssl'] ?? true);
            $config['api_ssl'] = false;
        }
        $this->client->connect($config);
    }

    private function transportDisconnect(): void
    {
        try {
            $this->client->disconnect();
        } catch (\Throwable) {
        }
    }

    private function routerTimezone(array $clock): DateTimeZone
    {
        $name = trim((string) ($clock['time-zone-name'] ?? $clock['time_zone_name'] ?? ''));
        if ($name !== '') {
            try {
                return new DateTimeZone($name);
            } catch (\Throwable) {
            }
        }
        return new DateTimeZone('UTC');
    }

    private function userPayload(array $attributes, bool $creating = true): array
    {
        $allowed = ['name', 'password', 'profile', 'server', 'comment', 'limit-uptime', 'limit-bytes-total', 'mac-address', 'disabled'];
        $payload = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $attributes) && $attributes[$key] !== null && $attributes[$key] !== '') {
                $payload[$key] = $attributes[$key];
            }
        }
        if ($creating && empty($payload['name'])) {
            throw new RuntimeException('Hotspot username is required.');
        }
        return $payload;
    }
}
