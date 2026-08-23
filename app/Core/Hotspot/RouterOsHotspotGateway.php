<?php

declare(strict_types=1);

namespace Ispluka\Core\Hotspot;

use DateTimeImmutable;
use DateTimeZone;
use Ispluka\Core\Database\Database;
use Ispluka\Core\Network\RouterOsApiClient;
use Ispluka\Core\Security\SecretBox;
use RuntimeException;

final class RouterOsHotspotGateway implements MikroTikHotspotGateway
{
    public function __construct(
        private readonly Database $db,
        private readonly SecretBox $secrets,
    ) {
    }

    public function routerTime(int $routerId): DateTimeImmutable
    {
        $rows = $this->command($routerId, '/system/clock/print');
        $row = $rows[0] ?? [];
        $date = trim((string) ($row['date'] ?? ''));
        $time = trim((string) ($row['time'] ?? ''));
        if ($date === '' || $time === '') {
            throw new RuntimeException('MikroTik did not return router clock.');
        }

        $timezone = trim((string) ($row['time-zone-name'] ?? 'UTC'));
        try {
            $tz = new DateTimeZone($timezone);
        } catch (\Throwable) {
            $tz = new DateTimeZone('UTC');
        }

        return new DateTimeImmutable($date . ' ' . $time, $tz);
    }

    public function activeUsers(int $routerId): array
    {
        return $this->command($routerId, '/ip/hotspot/active/print');
    }

    public function disconnect(int $routerId, string $username): void
    {
        foreach ($this->activeUsers($routerId) as $row) {
            if ((string) ($row['user'] ?? '') !== $username) {
                continue;
            }

            $id = (string) ($row['.id'] ?? '');
            if ($id !== '') {
                $this->command($routerId, '/ip/hotspot/active/remove', ['numbers' => $id]);
            }
        }
    }

    public function createUser(int $routerId, array $attributes): void
    {
        $args = [
            'name' => (string) ($attributes['username'] ?? ''),
            'password' => (string) ($attributes['password'] ?? ''),
        ];

        foreach (['profile', 'rate-limit', 'limit-uptime', 'limit-bytes-total', 'shared-users', 'mac-address', 'comment'] as $key) {
            if (array_key_exists($key, $attributes) && $attributes[$key] !== null && $attributes[$key] !== '') {
                $args[$key] = (string) $attributes[$key];
            }
        }

        if ($args['name'] === '' || $args['password'] === '') {
            throw new RuntimeException('Hotspot username and password are required.');
        }

        $this->command($routerId, '/ip/hotspot/user/add', $args);
    }

    public function updateUser(int $routerId, string $username, array $attributes): void
    {
        $rows = $this->command($routerId, '/ip/hotspot/user/print', ['?name' => $username]);
        $id = (string) ($rows[0]['.id'] ?? '');
        if ($id === '') {
            throw new RuntimeException('MikroTik Hotspot user not found.');
        }

        $args = ['numbers' => $id];
        foreach (['password', 'profile', 'rate-limit', 'limit-uptime', 'limit-bytes-total', 'shared-users', 'mac-address', 'comment', 'disabled'] as $key) {
            if (array_key_exists($key, $attributes)) {
                $args[$key] = (string) $attributes[$key];
            }
        }

        $this->command($routerId, '/ip/hotspot/user/set', $args);
    }

    public function disableUser(int $routerId, string $username): void
    {
        $this->updateUser($routerId, $username, ['disabled' => 'yes']);
    }

    public function enableUser(int $routerId, string $username): void
    {
        $this->updateUser($routerId, $username, ['disabled' => 'no']);
    }

    /** @return array<int,array<string,string>> */
    private function command(int $routerId, string $command, array $args = []): array
    {
        $stmt = $this->db->pdo()->prepare(
            "SELECT host, api_port, username, encrypted_password, verify_ssl
             FROM routers
             WHERE id = :id AND status = 'active'"
        );
        $stmt->execute([':id' => $routerId]);
        $router = $stmt->fetch();

        if (!is_array($router)) {
            throw new RuntimeException('MikroTik router not found or inactive.');
        }

        $router['password'] = $this->secrets->decrypt((string) $router['encrypted_password']);

        $client = new RouterOsApiClient();
        try {
            $client->connect($router);
            return $client->command($command, $args);
        } finally {
            $client->disconnect();
        }
    }
}
