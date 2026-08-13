<?php

declare(strict_types=1);
use PDO;
return new class {
 public function up(PDO $pdo): void {
  $pdo->exec('CREATE INDEX IF NOT EXISTS idx_customers_tenant_status ON customers(tenant_id,status)');
  $pdo->exec('CREATE INDEX IF NOT EXISTS idx_invoices_tenant_status_due ON invoices(tenant_id,status,due_date)');
  $pdo->exec('CREATE INDEX IF NOT EXISTS idx_payments_tenant_status_paid ON payments(tenant_id,status,paid_at)');
  $pdo->exec('CREATE INDEX IF NOT EXISTS idx_audit_logs_tenant_created ON audit_logs(tenant_id,created_at DESC)');
 }
 public function down(PDO $pdo): void {
  $pdo->exec('DROP INDEX IF EXISTS idx_customers_tenant_status');
  $pdo->exec('DROP INDEX IF EXISTS idx_invoices_tenant_status_due');
  $pdo->exec('DROP INDEX IF EXISTS idx_payments_tenant_status_paid');
  $pdo->exec('DROP INDEX IF EXISTS idx_audit_logs_tenant_created');
 }
};
