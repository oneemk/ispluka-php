<?php

declare(strict_types=1);

use Ispluka\Database\Migrations\MigrationInterface;

return new class implements MigrationInterface {
    public function up(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS payment_idempotency (
            id BIGSERIAL PRIMARY KEY,
            tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
            idempotency_key VARCHAR(128) NOT NULL,
            operation VARCHAR(64) NOT NULL,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (tenant_id, idempotency_key, operation)
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_payment_idempotency_created_at ON payment_idempotency(created_at)');
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS payment_idempotency');
    }
};
