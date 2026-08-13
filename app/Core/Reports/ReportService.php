<?php

declare(strict_types=1);
namespace Ispluka\Core\Reports;
use Ispluka\Core\Database\Database;
final class ReportService {
 public function __construct(private readonly Database $db) {}
 public function dashboard(int $tenantId): array {
  $q=$this->db->pdo(); $out=[];
  $queries=['customers'=>'SELECT COUNT(*) FROM customers WHERE tenant_id=:t','active_services'=>"SELECT COUNT(*) FROM customer_services WHERE tenant_id=:t AND status='active'",'pending_invoices'=>"SELECT COUNT(*) FROM invoices WHERE tenant_id=:t AND status IN ('draft','issued','overdue')",'payments_total'=>"SELECT COALESCE(SUM(amount),0) FROM payments WHERE tenant_id=:t AND status='completed' AND paid_at >= CURRENT_DATE"];
  foreach($queries as $k=>$sql){$s=$q->prepare($sql);$s->execute([':t'=>$tenantId]);$out[$k]=$s->fetchColumn();} return $out;
 }
}
