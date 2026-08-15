<?php

declare(strict_types=1);

use Ispluka\Database\Migrations\MigrationInterface;

return new class implements MigrationInterface
{
    public function up(PDO $pdo): void
    {
        $pdo->exec('ALTER TABLE customer_services RENAME COLUMN secret_hash TO encrypted_secret');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_customer_services_tenant_type_status ON customer_services (tenant_id, service_type, status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_customer_services_tenant_next_billing ON customer_services (tenant_id, next_billing_date) WHERE status IN (\'active\', \'suspended\')');
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS uq_customer_services_tenant_username ON customer_services (tenant_id, lower(username)) WHERE username IS NOT NULL');
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP INDEX IF EXISTS uq_customer_services_tenant_username');
        $pdo->exec('DROP INDEX IF EXISTS idx_customer_services_tenant_next_billing');
        $pdo->exec('DROP INDEX IF EXISTS idx_customer_services_tenant_type_status');
        $pdo->exec('ALTER TABLE customer_services RENAME COLUMN encrypted_secret TO secret_hash');
    }
};
