<?php

declare(strict_types=1);

use Ispluka\Database\Migrations\MigrationInterface;
use PDO;

return new class implements MigrationInterface
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS pppoe_suspend_profiles (
            id BIGSERIAL PRIMARY KEY,
            tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
            router_id BIGINT NOT NULL REFERENCES routers(id) ON DELETE CASCADE,
            profile_name VARCHAR(120) NOT NULL,
            address_list VARCHAR(120) NOT NULL,
            portal_url TEXT NOT NULL,
            portal_ips JSONB NOT NULL DEFAULT '[]'::jsonb,
            enabled BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (tenant_id, router_id),
            UNIQUE (tenant_id, profile_name)
        )");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS pppoe_suspend_profiles');
    }
};
