<?php

declare(strict_types=1);

namespace Ispluka\Services;

use Ispluka\Core\Network\RouterOsClient;
use Ispluka\Core\Security\SecretBox;
use Ispluka\Repositories\RouterRepository;
use InvalidArgumentException;
use RuntimeException;

final class RouterService
{
    public function __construct(private readonly RouterRepository $routers, private readonly SecretBox $secrets) {}

    public function create(int $tenantId, array $data): int
    {
        $host = trim((string)($data['host'] ?? ''));
        $name = trim((string)($data['name'] ?? ''));
        $code = trim((string)($data['code'] ?? ''));
        $username = trim((string)($data['username'] ?? ''));
        $password = (string)($data['password'] ?? '');
        if ($tenantId <= 0 || $name === '' || $code === '' || $host === '' || $username === '' || $password === '') {
            throw new InvalidArgumentException('Router name, code, host, username and password are required.');
        }
        return $this->routers->create($tenantId, [
            'name'=>$name, 'code'=>$code, 'host'=>$host,
            'api_port'=>(int)($data['api_port'] ?? 8728),
            'api_ssl_port'=>isset($data['api_ssl_port']) ? (int)$data['api_ssl_port'] : null,
            'username'=>$username, 'encrypted_password'=>$this->secrets->encrypt($password),
            'verify_ssl'=>(bool)($data['verify_ssl'] ?? true), 'status'=>'active', 'metadata'=>$data['metadata'] ?? [],
        ]);
    }

    public function testConnection(int $tenantId, int $routerId): array
    {
        $router = $this->routers->find($tenantId, $routerId);
        if ($router === null) throw new RuntimeException('Router not found.');
        $port = !empty($router['api_ssl_port']) ? (int)$router['api_ssl_port'] : (int)$router['api_port'];
        $client = new RouterOsClient((string)$router['host'], $port, 5, (bool)$router['verify_ssl']);
        try {
            $client->connect((string)$router['username'], $this->secrets->decrypt((string)$router['encrypted_password']));
            $identity = $client->command('/system/identity/print');
            $this->routers->markConnection($tenantId, $routerId, true);
            return ['ok'=>true, 'identity'=>$identity[0]['=name'] ?? null];
        } catch (\Throwable $e) {
            $this->routers->markConnection($tenantId, $routerId, false, $e->getMessage());
            return ['ok'=>false, 'error'=>'Router connection failed.'];
        } finally {
            $client->disconnect();
        }
    }

    public function provisionPppoe(int $tenantId, int $routerId, string $username, string $password, string $profile): void
    {
        $this->executeForRouter($tenantId, $routerId, '/ppp/secret/add', [
            'name'=>$username, 'password'=>$password, 'service'=>'pppoe', 'profile'=>$profile,
        ]);
    }

    public function suspendPppoe(int $tenantId, int $routerId, string $username): void
    {
        $this->executeForRouter($tenantId, $routerId, '/ppp/secret/disable', ['numbers'=>$this->findPppSecret($tenantId, $routerId, $username)]);
    }

    public function restorePppoe(int $tenantId, int $routerId, string $username): void
    {
        $this->executeForRouter($tenantId, $routerId, '/ppp/secret/enable', ['numbers'=>$this->findPppSecret($tenantId, $routerId, $username)]);
    }

    private function findPppSecret(int $tenantId, int $routerId, string $username): string
    {
        $router = $this->routers->find($tenantId, $routerId);
        if ($router === null) throw new RuntimeException('Router not found.');
        $client = $this->client($router);
        try {
            $rows = $client->command('/ppp/secret/print', ['?name'=>$username]);
            if (empty($rows[0]['=.id'])) throw new RuntimeException('PPPoE account not found.');
            return $rows[0]['=.id'];
        } finally { $client->disconnect(); }
    }

    private function executeForRouter(int $tenantId, int $routerId, string $command, array $arguments): void
    {
        $router = $this->routers->find($tenantId, $routerId);
        if ($router === null) throw new RuntimeException('Router not found.');
        $client = $this->client($router);
        try {
            $client->command($command, $arguments);
            $this->routers->markConnection($tenantId, $routerId, true);
        } catch (\Throwable $e) {
            $this->routers->markConnection($tenantId, $routerId, false, $e->getMessage());
            throw new RuntimeException('Router operation failed.', 0, $e);
        } finally { $client->disconnect(); }
    }

    private function client(array $router): RouterOsClient
    {
        $port = !empty($router['api_ssl_port']) ? (int)$router['api_ssl_port'] : (int)$router['api_port'];
        $client = new RouterOsClient((string)$router['host'], $port, 5, (bool)$router['verify_ssl']);
        $client->connect((string)$router['username'], $this->secrets->decrypt((string)$router['encrypted_password']));
        return $client;
    }
}
