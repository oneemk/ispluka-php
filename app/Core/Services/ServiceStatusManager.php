<?php

declare(strict_types=1);

namespace Ispluka\Core\Services;

use Ispluka\Core\Database\Database;
use RuntimeException;

final class ServiceStatusManager
{
    public function __construct(private readonly Database $database) {}

    public function suspend(int $tenantId, int $serviceId, string $reason = 'billing_due'): bool
    {
        return $this->change($tenantId, $serviceId, 'suspended', $reason);
    }

    public function restore(int $tenantId, int $serviceId, string $reason = 'payment_received'): bool
    {
        return $this->change($tenantId, $serviceId, 'active', $reason);
    }

    private function change(int $tenantId, int $serviceId, string $status, string $reason): bool
    {
        $pdo = $this->database->pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE customer_services SET status = :status, suspension_reason = :reason, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND tenant_id = :tenant_id AND status <> :status RETURNING id');
            $stmt->execute([':status'=>$status, ':reason'=>$reason, ':id'=>$serviceId, ':tenant_id'=>$tenantId]);
            $changed = $stmt->fetchColumn() !== false;
            $pdo->commit();
            return $changed;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw new RuntimeException('Unable to change service status.', 0, $e);
        }
    }
}
