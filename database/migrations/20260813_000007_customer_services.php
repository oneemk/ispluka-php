<?php

declare(strict_types=1);
return new class {
 public function up(PDO $pdo): void {
  $pdo->exec("CREATE TABLE IF NOT EXISTS customer_services (id BIGSERIAL PRIMARY KEY, tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE, customer_id BIGINT NOT NULL REFERENCES customers(id) ON DELETE CASCADE, package_id BIGINT NULL REFERENCES packages(id) ON DELETE SET NULL, router_id BIGINT NULL REFERENCES routers(id) ON DELETE SET NULL, connection_type VARCHAR(20) NOT NULL DEFAULT 'pppoe', username VARCHAR(120), password_encrypted TEXT, status VARCHAR(20) NOT NULL DEFAULT 'active', auto_suspend BOOLEAN NOT NULL DEFAULT TRUE, billing_day SMALLINT NOT NULL DEFAULT 1 CHECK (billing_day BETWEEN 1 AND 28), next_billing_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP, suspended_at TIMESTAMPTZ NULL, suspended_reason VARCHAR(255), created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE (tenant_id, username))");
  $pdo->exec('CREATE INDEX IF NOT EXISTS idx_customer_services_tenant_customer ON customer_services(tenant_id,customer_id)');
  $pdo->exec('CREATE INDEX IF NOT EXISTS idx_customer_services_billing ON customer_services(status,next_billing_at)');
 }
 public function down(PDO $pdo): void { $pdo->exec('DROP TABLE IF EXISTS customer_services'); }
};
