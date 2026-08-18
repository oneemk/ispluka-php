<?php

declare(strict_types=1);

namespace Ispluka\Core\Hotspot;

use PDO;
use Throwable;

final class HotspotAuditService
{
    public function __construct(private readonly PDO $pdo) {}

    public function record(
        int $tenantId,
        string $action,
        string $status,
        ?int $routerId = null,
        ?int $hotspotUserId = null,
        ?int $actorUserId = null,
        array $details = [],
    ): void {
        if ($tenantId < 1 || trim($action) === '') {
            return;
        }

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO hotspot_operation_logs
                 (tenant_id,router_id,hotspot_user_id,actor_user_id,action,status,details)
                 VALUES (:tenant_id,:router_id,:hotspot_user_id,:actor_user_id,:action,:status,CAST(:details AS jsonb))'
            );
            $statement->execute([
                ':tenant_id' => $tenantId,
                ':router_id' => $routerId,
                ':hotspot_user_id' => $hotspotUserId,
                ':actor_user_id' => $actorUserId,
                ':action' => mb_substr($action, 0, 100),
                ':status' => mb_substr($status, 0, 30),
                ':details' => json_encode($details, JSON_THROW_ON_ERROR),
            ]);
        } catch (Throwable) {
            // Audit logging must never turn a successful network operation into a failure.
        }
    }
}
