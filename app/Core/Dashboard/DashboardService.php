<?php

declare(strict_types=1);
namespace Ispluka\Core\Dashboard;
use Ispluka\Core\Database\Database;
final class DashboardService {
 public function __construct(private readonly Database $db) {}
 public function summary(int $tenantId):array { $pdo=$this->db->pdo();$q=function(string $sql)use($pdo,$tenantId){$s=$pdo->prepare($sql);$s->execute([':t'=>$tenantId]);return$s->fetchColumn();};return['customers'=>(int)$q('SELECT COUNT(*) FROM customers WHERE tenant_id=:t'),'active_services'=>(int)$q("SELECT COUNT(*) FROM customer_services WHERE tenant_id=:t AND status='active'"),'suspended_services'=>(int)$q("SELECT COUNT(*) FROM customer_services WHERE tenant_id=:t AND status='suspended'"),'overdue_invoices'=>(int)$q("SELECT COUNT(*) FROM invoices WHERE tenant_id=:t AND status='overdue'"),'outstanding'=>(int)$q('SELECT COALESCE(SUM(total-paid_amount),0) FROM invoices WHERE tenant_id=:t AND status IN (\'issued\',\'partial\',\'overdue\')'),'monthly_collected'=>(int)$q("SELECT COALESCE(SUM(amount),0) FROM payments WHERE tenant_id=:t AND status='completed' AND paid_at>=date_trunc('month',CURRENT_TIMESTAMP)")];}
}
