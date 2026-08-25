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
        if ($tenantId <= 0 || $invoiceId <= 0 || $amountMinor <= 0) {
            throw new RuntimeException('Invalid payment request.');
        }

        $pdo = $this->database->pdo();
        $stmt = $pdo->prepare(
            "SELECT i.id,i.customer_id,i.total,i.paid_amount,i.status,
                    c.id AS customer_id,c.name AS customer_name,c.phone AS customer_phone,c.email AS customer_email
             FROM invoices i
             JOIN customers c ON c.id=i.customer_id AND c.tenant_id=i.tenant_id
             WHERE i.id=:id AND i.tenant_id=:tenant_id
             LIMIT 1"
        );
        $stmt->execute([':id' => $invoiceId, ':tenant_id' => $tenantId]);
        $invoice = $stmt->fetch();
        if (!is_array($invoice)) throw new RuntimeException('Invoice not found.');
        if ((string) $invoice['status'] === 'paid') throw new RuntimeException('Invoice is already paid.');

        $gateway = $this->gateways->get($gatewayCode);
        $payment = [
            'tenant_id' => $tenantId,
            'invoice_id' => $invoiceId,
            'customer_id' => (int) $invoice['customer_id'],
            'amount' => $amountMinor,
            'amount_minor' => $amountMinor,
        ];
        $customer = [
            'id' => (int) $invoice['customer_id'],
            'name' => (string) ($invoice['customer_name'] ?? ''),
            'phone' => (string) ($invoice['customer_phone'] ?? ''),
            'email' => (string) ($invoice['customer_email'] ?? ''),
        ];

        $result = $gateway->initiate($payment, $customer);
        return new PaymentResult(
            (string) ($result['status'] ?? 'failed'),
            (string) ($result['reference'] ?? ''),
            $amountMinor,
            is_array($result['raw'] ?? null) ? $result['raw'] : $result,
        );
    }
}
