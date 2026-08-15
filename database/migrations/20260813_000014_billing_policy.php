<?php

declare(strict_types=1);
return new class {
 public function up(PDO $pdo):void{$pdo->exec("CREATE TABLE IF NOT EXISTS billing_policies (id BIGSERIAL PRIMARY KEY,tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,grace_days SMALLINT NOT NULL DEFAULT 3 CHECK(grace_days>=0 AND grace_days<=60),auto_suspend BOOLEAN NOT NULL DEFAULT TRUE,auto_restore BOOLEAN NOT NULL DEFAULT TRUE,suspend_on_overdue BOOLEAN NOT NULL DEFAULT TRUE,created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,UNIQUE(tenant_id))");$pdo->exec('CREATE INDEX IF NOT EXISTS idx_billing_policies_tenant ON billing_policies(tenant_id)');}
 public function down(PDO $pdo):void{$pdo->exec('DROP TABLE IF EXISTS billing_policies');}
};
