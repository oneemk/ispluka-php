<?php

declare(strict_types=1);
return new class {
 public function up(PDO $pdo):void{$pdo->exec("CREATE TABLE IF NOT EXISTS network_jobs (id BIGSERIAL PRIMARY KEY,tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,service_id BIGINT NOT NULL REFERENCES customer_services(id) ON DELETE CASCADE,action VARCHAR(30) NOT NULL,payload JSONB NOT NULL DEFAULT '{}'::jsonb,status VARCHAR(20) NOT NULL DEFAULT 'pending',attempts SMALLINT NOT NULL DEFAULT 0,max_attempts SMALLINT NOT NULL DEFAULT 5,last_error TEXT NULL,available_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP)");$pdo->exec('CREATE INDEX IF NOT EXISTS idx_network_jobs_queue ON network_jobs(status,available_at)');$pdo->exec('CREATE INDEX IF NOT EXISTS idx_network_jobs_tenant_service ON network_jobs(tenant_id,service_id)');}
 public function down(PDO $pdo):void{$pdo->exec('DROP TABLE IF EXISTS network_jobs');}
};
