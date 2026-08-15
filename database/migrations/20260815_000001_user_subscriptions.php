<?php

declare(strict_types=1);

use Ispluka\Database\Migrations\MigrationInterface;

return new class implements MigrationInterface
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_subscriptions (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            plan_code VARCHAR(60) NOT NULL DEFAULT 'reseller',
            amount NUMERIC(12,2) NOT NULL DEFAULT 0 CHECK (amount >= 0),
            starts_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ends_at TIMESTAMPTZ,
            status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('trial','active','past_due','suspended','cancelled','expired')),
            metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_user_subscriptions_user ON user_subscriptions (user_id, ends_at DESC)');
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS user_subscriptions');
    }
};
