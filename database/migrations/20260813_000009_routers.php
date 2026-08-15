<?php

declare(strict_types=1);
return new class {
 public function up(PDO $pdo): void { $pdo->exec("CREATE TABLE IF NOT EXISTS routers (id BIGSERIAL PRIMARY KEY, tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE, name VARCHAR(120) NOT NULL, host VARCHAR(255) NOT NULL, api_port INTEGER NOT NULL DEFAULT 8728, username VARCHAR(120) NOT NULL, password_encrypted TEXT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'active', last_seen_at TIMESTAMPTZ NULL, last_error TEXT NULL, created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE(tenant_id,name))"); $pdo->exec('CREATE INDEX IF NOT EXISTS idx_routers_tenant_status ON routers(tenant_id,status)'); }
 public function down(PDO $pdo): void { $pdo->exec('DROP TABLE IF EXISTS routers'); }
};
