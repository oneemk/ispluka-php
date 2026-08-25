<?php

declare(strict_types=1);

namespace Ispluka\Core\Billing;

use Ispluka\Core\Database\Database;
use RuntimeException;

final class PaymentAllocator
{
    public function __construct(private readonly Database $db) {}

    public function allocate(int $tenantId, int $paymentId, int $invoiceId, int $amount): void
    {
        if ($tenantId <= 0 || $paymentId <= 0 || $invoiceId <= 0 || $amount <= 0) {
            throw new RuntimeException('Payment allocation must be positive and valid.');
        }

        $pdo = $this->db->pdo();
        $pdo->beginTransaction();
        try {
            $s = $pdo->prepare(
                "SELECT i.id,i.total,i.paid_amount,p.amount AS payment_amount,
                        COALESCE((SELECT SUM(pa.amount) FROM payment_allocations pa WHERE pa.payment_id=p.id),0) AS allocated_amount,
                        COALESCE((SELECT SUM(pa.amount) FROM payment_allocations pa WHERE pa.payment_id=p.id AND pa.invoice_id=i.id),0) AS invoice_allocated
                 FROM invoices i
                 JOIN payments p ON p.id=:p AND p.tenant_id=i.tenant_id
                 WHERE i.tenant_id=:t AND i.id=:i AND p.status='completed'
                 FOR UPDATE"
            );
            $s->execute([':t' => $tenantId, ':i' => $invoiceId, ':p' => $paymentId]);
            $r = $s->fetch();
            if (!$r) throw new RuntimeException('Invoice/payment not found.');

            $invoiceAllocated = (int) $r['invoice_allocated'];
            if ($invoiceAllocated >= $amount) {
                $pdo->commit();
                return;
            }

            $remainingInvoice = max(0, (int) $r['total'] - (int) $r['paid_amount']);
            $remainingPayment = max(0, (int) $r['payment_amount'] - (int) $r['allocated_amount']);
            $needed = $amount - $invoiceAllocated;
            if ($needed > $remainingInvoice || $needed > $remainingPayment) {
                throw new RuntimeException('Allocation exceeds available amount.');
            }

            $a = $pdo->prepare('INSERT INTO payment_allocations(payment_id,invoice_id,amount) VALUES(:p,:i,:a)');
            $a->execute([':p' => $paymentId, ':i' => $invoiceId, ':a' => $needed]);
            $newPaid = (int) $r['paid_amount'] + $needed;
            $status = $newPaid >= (int) $r['total'] ? 'paid' : 'partial';
            $u = $pdo->prepare('UPDATE invoices SET paid_amount=paid_amount+:a,status=:s,updated_at=CURRENT_TIMESTAMP WHERE id=:i AND tenant_id=:t');
            $u->execute([':a' => $needed, ':s' => $status, ':i' => $invoiceId, ':t' => $tenantId]);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
