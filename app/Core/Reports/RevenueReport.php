<?php

declare(strict_types=1);
namespace Ispluka\Core\Reports;
use Ispluka\Core\Database\Database;
final class RevenueReport {
 public function __construct(private readonly Database $db) {}
 public function daily(int $tenantId,string $from,string $to):array{$s=$this->db->pdo()->prepare("SELECT DATE(paid_at) day,COALESCE(SUM(amount),0) amount,COUNT(*) transactions FROM payments WHERE tenant_id=:t AND status='completed' AND paid_at>=CAST(:f AS date) AND paid_at<CAST(:to AS date)+INTERVAL '1 day' GROUP BY DATE(paid_at) ORDER BY day");$s->execute([':t'=>$tenantId,':f'=>$from,':to'=>$to]);return$s->fetchAll();}
 public function outstanding(int $tenantId):array{$s=$this->db->pdo()->prepare("SELECT status,COUNT(*) invoices,COALESCE(SUM(total-paid_amount),0) outstanding FROM invoices WHERE tenant_id=:t AND status IN ('issued','partial','overdue') GROUP BY status ORDER BY status");$s->execute([':t'=>$tenantId]);return$s->fetchAll();}
}
