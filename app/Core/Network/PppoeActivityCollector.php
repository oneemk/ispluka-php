<?php

declare(strict_types=1);

namespace Ispluka\Core\Network;

use Closure;
use RuntimeException;

final class PppoeActivityCollector
{
    /** @param Closure(string,array<string,string>):array<int,array<string,mixed>> $command */
    public function __construct(
        private readonly PppoeActivityRepository $repository,
        private readonly Closure $command,
    ) {}

    public function collect(int $tenantId, int $routerId, int $limit = 1000): int
    {
        $rows = ($this->command)('/ppp/active/print', [
            '=.proplist' => 'name,address,uptime,rx-byte,tx-byte',
            '?disabled' => 'no',
        ]);

        if (!is_array($rows)) {
            throw new RuntimeException('Unable to collect MikroTik PPPoE sessions.');
        }

        $count = 0;
        $now = time();

        foreach (array_slice($rows, 0, max(1, min(1000, $limit))) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $username = trim((string) ($row['name'] ?? ''));
            if ($username === '') {
                continue;
            }

            $this->repository->upsert(new PppoeActivityState(
                $tenantId,
                $routerId,
                $username,
                true,
                $row['address'] ?? null,
                $now,
                $this->duration($row['uptime'] ?? null),
                $this->bytes($row['rx-byte'] ?? null),
                $this->bytes($row['tx-byte'] ?? null),
                null,
                null,
                false,
            ));

            $count++;
        }

        return $count;
    }

    private function bytes(mixed $value): ?int
    {
        return is_numeric($value) ? max(0, (int) $value) : null;
    }

    private function duration(mixed $value): ?int
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        if (!preg_match('/^(?:(\d+)w)?(?:(\d+)d)?(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$/', $value, $matches)) {
            return null;
        }

        return (int) ($matches[1] ?? 0) * 604800
            + (int) ($matches[2] ?? 0) * 86400
            + (int) ($matches[3] ?? 0) * 3600
            + (int) ($matches[4] ?? 0) * 60
            + (int) ($matches[5] ?? 0);
    }
}
