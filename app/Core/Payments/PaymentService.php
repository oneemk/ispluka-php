<?php

declare(strict_types=1);

namespace Ispluka\Core\Payments;

use Ispluka\Core\Database\Database;
use RuntimeException;

final class PaymentService
{
    public function __construct(private readonly Database $database, private readonly GatewayRegistry $gateways) {}

    public function initiate(int $tenantId, int $invoiceId, int $amountMinor, string $gatewayCode): PaymentResult
    {
        if ($amountMinor <= 0) throw new RuntimeException('Payment amount must be positive.');
        $pdo = $this->database->pdo();
        $stmt = $pdo->prepare('SELECT id, total, status FROM invoices WHERE id = :id AND tenant_id = :tenant_id FOR UPDATE');
        $pdo->beginTransaction();
        try {
            $stmt->execute([':id' => $invoiceId, ':tenant_id' => $tenantId]);
            $invoice = $stmt->fetch();
            if (!$invoice) throw new RuntimeException('Invoice not found.');
            if ((string)$invoice['status'] === 'paid') throw new RuntimeException('Invoice is already paid.');

            $gateway = $this->gateways->get($gatewayCode);
            $result = $gateway->initiate(['tenant_id' => $tenantId, 'invoice_id' => $invoiceId, 'amount_minor' => $amountMinor]);
            $pdo->commit();
            return new PaymentResult($result['status'], $result['reference'], $amountMinor, $result['raw'] ?? []);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
