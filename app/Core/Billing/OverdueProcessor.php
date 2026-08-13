<?php

declare(strict_types=1);
namespace Ispluka\Core\Billing;
use Ispluka\Core\Database\Database;
final class OverdueProcessor {
 public function __construct(private readonly Database $db) {}
 public function markOverdue(int $batch=200):int { $batch=min(max($batch,1),500);$s=$this->db->pdo()->prepare("UPDATE invoices SET status='overdue',updated_at=CURRENT_TIMESTAMP WHERE id IN (SELECT id FROM invoices WHERE status IN ('issued','partial') AND due_date<CURRENT_DATE ORDER BY due_date LIMIT :b) RETURNING id");$s->bindValue(':b',$batch,\PDO::PARAM_INT);$s->execute();return count($s->fetchAll()); }
}
