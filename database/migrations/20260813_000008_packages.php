<?php

declare(strict_types=1);
return new class {
 public function up(PDO $pdo): void {
  $pdo->exec("CREATE TABLE IF NOT EXISTS packages (id BIGSERIAL PRIMARY KEY, tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE, name VARCHAR(120) NOT NULL, download_kbps INTEGER NOT NULL CHECK(download_kbps > 0), upload_kbps INTEGER NOT NULL CHECK(upload_kbps > 0), monthly_price BIGINT NOT NULL CHECK(monthly_price >= 0), connection_type VARCHAR(20) NOT NULL DEFAULT 'pppoe', shared_users SMALLINT NOT NULL DEFAULT 1 CHECK(shared_users > 0), status VARCHAR(20) NOT NULL DEFAULT 'active', created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE(tenant_id,name))");
  $pdo->exec('CREATE INDEX IF NOT EXISTS idx_packages_tenant_status ON packages(tenant_id,status)');
 }
 public function down(PDO $pdo): void { $pdo->exec('DROP TABLE IF EXISTS packages'); }
};
