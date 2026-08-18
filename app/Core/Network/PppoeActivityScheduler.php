<?php

declare(strict_types=1);

namespace Ispluka\Core\Network;

use Closure;
use Throwable;

final class PppoeActivityScheduler
{
    /** @var Closure(string,int):bool */
    private readonly Closure $lock;

    /** @var Closure(string):void */
    private readonly Closure $unlock;

    public function __construct(
        private readonly PppoeActivityCollector $collector,
        callable $lock,
        callable|int|null $unlock = null,
        int $intervalSeconds = 60,
    ) {
        $this->lock = Closure::fromCallable($lock);

        // Backward compatibility: older callers passed the interval as the
        // third argument. New callers may pass an explicit unlock callback.
        if (is_int($unlock)) {
            $intervalSeconds = $unlock;
            $unlock = null;
        }

        $this->unlock = $unlock !== null
            ? Closure::fromCallable($unlock)
            : static function (string $key): void {};
        $this->intervalSeconds = max(30, $intervalSeconds);
    }

    private readonly int $intervalSeconds;

    public function run(int $tenantId, int $routerId): array
    {
        $key = "pppoe-activity:{$tenantId}:{$routerId}";

        if (!(($this->lock)($key, $this->intervalSeconds))) {
            return ['status' => 'skipped', 'reason' => 'already_running'];
        }

        try {
            $count = $this->collector->collect($tenantId, $routerId);
            return ['status' => 'success', 'sessions' => $count];
        } catch (Throwable $e) {
            return ['status' => 'failed', 'sessions' => 0, 'error' => $e->getMessage()];
        } finally {
            try {
                ($this->unlock)($key);
            } catch (Throwable $ignore) {
            }
        }
    }
}
