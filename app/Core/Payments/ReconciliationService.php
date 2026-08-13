<?php

declare(strict_types=1);

namespace Ispluka\Core\Payments;

use Ispluka\Core\Database\Database;
use RuntimeException;

final class ReconciliationService
{
    public function __construct(private readonly Database $database) {}

    public function markSuccessful(int $tenantId, string $reference, int $amountMinor, string $gateway): void
    {
        if ($amountMinor <= 0 || trim($reference) === '') throw new RuntimeException('Invalid payment confirmation.');
        $pdo = $this->database->pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT id, invoice_id, amount FROM payments WHERE tenant_id = :tenant_id AND gateway_reference = :reference FOR UPDATE');
            $stmt->execute([':tenant_id'=>$tenantId, ':reference'=>$reference]);
            $payment = $stmt->fetch();
            if (!$payment) throw new RuntimeException('Payment transaction not found.');
            if ((int)$payment['amount'] !== $amountMinor) throw new RuntimeException('Payment amount mismatch.');
            $update = $pdo->prepare("UPDATE payments SET status = 'completed', gateway = :gateway, paid_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND status <> 'completed'");
            $update->execute([':gateway'=>$gateway, ':id'=>$payment['id']]);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
