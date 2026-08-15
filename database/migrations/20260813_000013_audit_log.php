<?php

declare(strict_types=1);
return new class {
 public function up(PDO $pdo):void{$pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (id BIGSERIAL PRIMARY KEY,tenant_id BIGINT NULL REFERENCES tenants(id) ON DELETE SET NULL,user_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,action VARCHAR(80) NOT NULL,entity_type VARCHAR(80) NULL,entity_id BIGINT NULL,ip_address INET NULL,user_agent TEXT NULL,request_id VARCHAR(64) NULL,metadata JSONB NOT NULL DEFAULT '{}'::jsonb,created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP)");$pdo->exec('CREATE INDEX IF NOT EXISTS idx_audit_tenant_created ON audit_logs(tenant_id,created_at DESC)');$pdo->exec('CREATE INDEX IF NOT EXISTS idx_audit_entity ON audit_logs(entity_type,entity_id,created_at DESC)');$pdo->exec('CREATE INDEX IF NOT EXISTS idx_audit_user_created ON audit_logs(user_id,created_at DESC)');}
 public function down(PDO $pdo):void{$pdo->exec('DROP TABLE IF EXISTS audit_logs');}
};
