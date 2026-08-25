<?php

declare(strict_types=1);

namespace Ispluka\Core\Payments;

use Ispluka\Core\Database\Database;
use RuntimeException;

final class PaymentProcessor
{
    public function __construct(private readonly Database $db) {}

    public function recordVerified(int $tenantId, int $customerId, int $amount, string $gateway, string $transactionId, array $meta = []): int
    {
        if ($tenantId <= 0 || $customerId <= 0 || $amount <= 0 || trim($gateway) === '' || trim($transactionId) === '') {
            throw new RuntimeException('Invalid verified payment.');
        }

        $gateway = strtolower(trim($gateway));
        $method = in_array($gateway, ['cash', 'bank', 'bkash', 'nagad', 'card', 'other'], true) ? $gateway : 'other';
        $reference = strtoupper(substr(preg_replace('/[^A-Za-z0-9._:-]+/', '-', $gateway . ':' . $transactionId) ?: '', 0, 120));
        if ($reference === '') throw new RuntimeException('Payment reference could not be generated.');

        $pdo = $this->db->pdo();
        $pdo->beginTransaction();
        try {
            $s = $pdo->prepare(
                "INSERT INTO payments
                    (tenant_id,customer_id,reference,method,gateway,amount,status,gateway_transaction_id,paid_at,metadata)
                 VALUES
                    (:t,:c,:reference,:method,:gateway,:amount,'completed',:transaction_id,CURRENT_TIMESTAMP,:metadata)
                 ON CONFLICT(tenant_id,reference)
                 DO UPDATE SET updated_at=CURRENT_TIMESTAMP
                 RETURNING id"
            );
            $s->execute([
                ':t' => $tenantId,
                ':c' => $customerId,
                ':reference' => $reference,
                ':method' => $method,
                ':gateway' => $gateway,
                ':amount' => $amount,
                ':transaction_id' => $transactionId,
                ':metadata' => json_encode($meta, JSON_THROW_ON_ERROR),
            ]);
            $id = (int) $s->fetchColumn();
            if ($id <= 0) throw new RuntimeException('Unable to record verified payment.');
            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
