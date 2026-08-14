<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;

use Throwable;

final class PppoeActivityScheduler
{
    /** @var callable(string):bool */
    public function __construct(
        private readonly PppoeActivityCollector $collector,
        private readonly $lock,
        private readonly int $intervalSeconds = 60,
    ) {}

    public function run(int $tenantId, int $routerId): array
    {
        $key = "pppoe-activity:{$tenantId}:{$routerId}";
        if (!(($this->lock)($key, max(30, $this->intervalSeconds)))) {
            return ['status' => 'skipped', 'reason' => 'already_running'];
        }
        try {
            $count = $this->collector->collect($tenantId, $routerId);
            return ['status' => 'success', 'sessions' => $count];
        } catch (Throwable $e) {
            return ['status' => 'failed', 'sessions' => 0, 'error' => $e->getMessage()];
        }
    }
}
