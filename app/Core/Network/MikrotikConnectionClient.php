<?php

declare(strict_types=1);

namespace Ispluka\Core\Network;

use RuntimeException;

final class MikrotikConnectionClient implements MikrotikClientInterface
{
    private ?MikrotikClientInterface $active = null;

    public function __construct(
        private readonly RouterOsApiClient $api,
        private readonly RouterOsSshClient $ssh,
    ) {}

    public function connect(array $router): void
    {
        $method = strtolower(trim((string)($router['connection_method'] ?? 'api')));
        if (!in_array($method, ['api', 'ssh'], true)) {
            throw new RuntimeException('Unsupported MikroTik connection method: ' . ($method !== '' ? $method : '(empty)') . '.');
        }

        // Never leave the previous transport open when a client instance is
        // reused for another router or connection method.
        $this->disconnect();
        $this->active = $method === 'ssh' ? $this->ssh : $this->api;

        try {
            $this->active->connect($router);
        } catch (\Throwable $e) {
            try {
                $this->active->disconnect();
            } catch (\Throwable $ignore) {
            }
            $this->active = null;
            throw $e;
        }
    }

    public function command(string $command, array $arguments = []): array
    {
        if ($this->active === null) {
            throw new RuntimeException('MikroTik connection is not open.');
        }
        return $this->active->command($command, $arguments);
    }

    public function disconnect(): void
    {
        if ($this->active !== null) {
            try {
                $this->active->disconnect();
            } finally {
                $this->active = null;
            }
        }
    }
}
