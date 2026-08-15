<?php

declare(strict_types=1);


return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("ALTER TABLE customer_services ADD COLUMN IF NOT EXISTS suspension_reason VARCHAR(255) NULL");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_customer_services_tenant_status ON customer_services(tenant_id, status)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_customer_services_billing ON customer_services(status, auto_suspend, next_billing_at)");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP INDEX IF EXISTS idx_customer_services_billing");
        $pdo->exec("DROP INDEX IF EXISTS idx_customer_services_tenant_status");
        $pdo->exec("ALTER TABLE customer_services DROP COLUMN IF EXISTS suspension_reason");
    }
};
