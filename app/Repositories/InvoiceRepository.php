<?php

declare(strict_types=1);

namespace Ispluka\Repositories;

use Ispluka\Core\Database\Database;
use PDO;

final class InvoiceRepository
{
    public function __construct(private readonly Database $database) {}

    public function create(int $tenantId, int $customerId, array $invoice, array $items): int
    {
        return $this->database->transaction()->run(function () use ($tenantId, $customerId, $invoice, $items): int {
            $pdo = $this->database->pdo();
            $stmt = $pdo->prepare('INSERT INTO invoices (tenant_id, customer_id, invoice_number, issue_date, due_date, subtotal, discount, tax, total, paid_amount, status, metadata) VALUES (:tenant_id,:customer_id,:number,:issue_date,:due_date,:subtotal,:discount,:tax,:total,0,:status,CAST(:metadata AS jsonb)) RETURNING id');
            $stmt->execute([
                ':tenant_id'=>$tenantId, ':customer_id'=>$customerId, ':number'=>$invoice['invoice_number'],
                ':issue_date'=>$invoice['issue_date'], ':due_date'=>$invoice['due_date'], ':subtotal'=>$invoice['subtotal'],
                ':discount'=>$invoice['discount'], ':tax'=>$invoice['tax'], ':total'=>$invoice['total'],
                ':status'=>$invoice['status'], ':metadata'=>json_encode($invoice['metadata'] ?? [], JSON_THROW_ON_ERROR),
            ]);
            $id = (int)$stmt->fetchColumn();
            $item = $pdo->prepare('INSERT INTO invoice_items (invoice_id, service_id, description, quantity, unit_price, discount, tax, total) VALUES (:invoice_id,:service_id,:description,:quantity,:unit_price,:discount,:tax,:total)');
            foreach ($items as $row) {
                $item->execute([
                    ':invoice_id'=>$id, ':service_id'=>$row['service_id'] ?? null, ':description'=>$row['description'],
                    ':quantity'=>$row['quantity'], ':unit_price'=>$row['unit_price'], ':discount'=>$row['discount'],
                    ':tax'=>$row['tax'], ':total'=>$row['total'],
                ]);
            }
            return $id;
        });
    }

    public function find(int $tenantId, int $invoiceId): ?array
    {
        $stmt = $this->database->pdo()->prepare('SELECT * FROM invoices WHERE tenant_id=:tenant_id AND id=:id');
        $stmt->execute([':tenant_id'=>$tenantId, ':id'=>$invoiceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listForCustomer(int $tenantId, int $customerId, int $limit=50, int $offset=0): array
    {
        $stmt = $this->database->pdo()->prepare('SELECT id, invoice_number, issue_date, due_date, subtotal, discount, tax, total, paid_amount, status, created_at FROM invoices WHERE tenant_id=:tenant_id AND customer_id=:customer_id ORDER BY issue_date DESC, id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':tenant_id',$tenantId,PDO::PARAM_INT); $stmt->bindValue(':customer_id',$customerId,PDO::PARAM_INT);
        $stmt->bindValue(':limit',min(max($limit,1),100),PDO::PARAM_INT); $stmt->bindValue(':offset',max($offset,0),PDO::PARAM_INT); $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
