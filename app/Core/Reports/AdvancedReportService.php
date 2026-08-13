<?php

declare(strict_types=1);
namespace Ispluka\Core\Reports;
use Ispluka\Core\Database\Database;
final class AdvancedReportService {
 public function __construct(private readonly Database $db) {}
 public function revenue(int $tenantId,string $from,string $to):array{$s=$this->db->pdo()->prepare("SELECT DATE(paid_at) day,COALESCE(SUM(amount),0) total,COUNT(*) transactions FROM payments WHERE tenant_id=:t AND status='completed' AND paid_at>=:f::date AND paid_at<(:to::date + INTERVAL '1 day') GROUP BY DATE(paid_at) ORDER BY day");$s->execute([':t'=>$tenantId,':f'=>$from,':to'=>$to]);return$s->fetchAll();}
 public function outstanding(int $tenantId):array{$s=$this->db->pdo()->prepare("SELECT status,COUNT(*) invoice_count,COALESCE(SUM(total-paid_amount),0) outstanding FROM invoices WHERE tenant_id=:t AND status IN ('issued','partial','overdue') GROUP BY status ORDER BY status");$s->execute([':t'=>$tenantId]);return$s->fetchAll();}
 public function serviceStatus(int $tenantId):array{$s=$this->db->pdo()->prepare('SELECT status,COUNT(*) count FROM customer_services WHERE tenant_id=:t GROUP BY status ORDER BY status');$s->execute([':t'=>$tenantId]);return$s->fetchAll();}
}
