<?php

declare(strict_types=1);

use Ispluka\Database\Migrations\MigrationInterface;

return new class implements MigrationInterface
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_invoices_tenant_issue ON invoices (tenant_id, issue_date DESC, id DESC)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_invoices_tenant_number ON invoices (tenant_id, invoice_number)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_invoices_overdue ON invoices (tenant_id, due_date) WHERE status IN ('unpaid','partial','overdue')");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_services_next_billing ON customer_services (tenant_id, next_billing_date, status) WHERE next_billing_date IS NOT NULL AND status IN ('active','suspended')");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP INDEX IF EXISTS idx_invoices_tenant_issue');
        $pdo->exec('DROP INDEX IF EXISTS idx_invoices_tenant_number');
        $pdo->exec('DROP INDEX IF EXISTS idx_invoices_overdue');
        $pdo->exec('DROP INDEX IF EXISTS idx_services_next_billing');
    }
};
