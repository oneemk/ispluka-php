<?php

declare(strict_types=1);

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS routers (id BIGSERIAL PRIMARY KEY, tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE, name VARCHAR(120) NOT NULL, code VARCHAR(80) NOT NULL, host VARCHAR(255) NOT NULL, api_port INTEGER NOT NULL DEFAULT 8728, api_ssl_port INTEGER NULL, username VARCHAR(120) NOT NULL, encrypted_password TEXT NOT NULL, verify_ssl BOOLEAN NOT NULL DEFAULT TRUE, status VARCHAR(20) NOT NULL DEFAULT 'offline', last_seen_at TIMESTAMPTZ NULL, last_error TEXT NULL, metadata JSONB NOT NULL DEFAULT '{}'::jsonb, created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE(tenant_id,name), UNIQUE(tenant_id,code))");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_routers_tenant_status ON routers(tenant_id,status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_routers_tenant_last_seen ON routers(tenant_id,last_seen_at DESC)');
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS routers');
    }
};
