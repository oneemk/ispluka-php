<?php

declare(strict_types=1);

namespace Ispluka\Core\Network;

use DateTimeImmutable;
use PDO;

final class PppoeInactivityReconciler
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly PppoeInactivityPolicy $policy,
        private readonly PppoeInactivityFindingStore $findings,
    ) {}

    /**
     * Evaluate inactivity only after a successful, authoritative router snapshot.
     * A failed router sync must never create or resolve inactivity findings.
     */
    public function reconcile(int $tenantId, int $routerId, bool $routerSyncHealthy, ?DateTimeImmutable $now = null): array
    {
        if ($tenantId < 1 || $routerId < 1) {
            return ['status' => 'skipped', 'reason' => 'invalid_context', 'opened' => 0, 'resolved' => 0];
        }

        if (!$routerSyncHealthy) {
            return ['status' => 'cannot_determine', 'opened' => 0, 'resolved' => 0];
        }

        $now ??= new DateTimeImmutable('now');
        $query = $this->pdo->prepare(
            'SELECT username, online, last_seen_at
             FROM pppoe_activity_state
             WHERE tenant_id=:t AND router_id=:r
             ORDER BY username'
        );
        $query->execute([':t' => $tenantId, ':r' => $routerId]);

        $opened = 0;
        $resolved = 0;

        foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $username = trim((string) ($row['username'] ?? ''));
            if ($username === '') {
                continue;
            }

            $lastSeen = $row['last_seen_at'] !== null ? (string) $row['last_seen_at'] : null;
            $status = $this->policy->status($lastSeen, true, $now);

            if ($status === 'inactive_20_plus') {
                $this->findings->openOrRefresh(
                    $tenantId,
                    $routerId,
                    $username,
                    sprintf('PPPoE customer has been inactive for %d+ days.', $this->policy->inactiveDays),
                    [
                        'status' => $status,
                        'online' => (bool) $row['online'],
                        'last_seen_at' => $lastSeen,
                        'inactive_days' => $this->policy->inactiveDays,
                    ],
                    $now->getTimestamp(),
                );
                $opened++;
                continue;
            }

            $this->findings->resolve($tenantId, $routerId, $username, $now->getTimestamp());
            $resolved++;
        }

        return [
            'status' => 'success',
            'opened' => $opened,
            'resolved' => $resolved,
        ];
    }
}
