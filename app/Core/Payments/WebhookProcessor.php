<?php

declare(strict_types=1);

namespace Ispluka\Core\Payments;

use Ispluka\Core\Billing\PaymentAllocator;
use Ispluka\Core\Database\Database;
use RuntimeException;

final class WebhookProcessor
{
    public function __construct(
        private readonly Database $db,
        private readonly GatewayRegistry $gateways,
        private readonly PaymentProcessor $payments,
        private readonly PaymentAllocator $allocator,
    ) {}

    public function handle(int $tenantId, string $gateway, array $payload): array
    {
        if ($tenantId <= 0 || trim($gateway) === '') throw new RuntimeException('Invalid payment callback.');

        $verified = $this->gateways->get($gateway)->verify($payload);
        if (($verified['status'] ?? '') !== 'verified') throw new RuntimeException('Payment verification failed.');

        $invoiceId = (int) ($payload['invoice_id'] ?? 0);
        $amount = (int) ($verified['amount'] ?? 0);
        $tx = trim((string) ($verified['transaction_id'] ?? $verified['reference'] ?? ''));
        if ($invoiceId <= 0 || $amount <= 0 || $tx === '') throw new RuntimeException('Incomplete payment callback.');

        $s = $this->db->pdo()->prepare(
            "SELECT customer_id,total,paid_amount,status
             FROM invoices
             WHERE tenant_id=:t AND id=:i
             LIMIT 1"
        );
        $s->execute([':t' => $tenantId, ':i' => $invoiceId]);
        $invoice = $s->fetch();
        if (!is_array($invoice)) throw new RuntimeException('Invoice not found.');
        if ((string) $invoice['status'] === 'paid') throw new RuntimeException('Invoice is already paid.');

        $remaining = max(0, (int) $invoice['total'] - (int) $invoice['paid_amount']);
        if ($remaining <= 0 || $amount > $remaining) throw new RuntimeException('Payment amount exceeds invoice balance.');

        $customerId = (int) $invoice['customer_id'];
        $paymentId = $this->payments->recordVerified($tenantId, $customerId, $amount, $gateway, $tx, $payload);
        $this->allocator->allocate($tenantId, $paymentId, $invoiceId, $amount);

        return ['payment_id' => $paymentId, 'invoice_id' => $invoiceId, 'status' => 'allocated'];
    }
}
