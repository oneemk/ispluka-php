<?php

declare(strict_types=1);
use PDO;
return new class {
 public function up(PDO $pdo):void{
  $pdo->exec("CREATE TABLE IF NOT EXISTS reseller_profiles (id BIGSERIAL PRIMARY KEY,tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,commission_percent NUMERIC(5,2) NOT NULL DEFAULT 0 CHECK(commission_percent>=0 AND commission_percent<=100),credit_limit BIGINT NOT NULL DEFAULT 0 CHECK(credit_limit>=0),balance BIGINT NOT NULL DEFAULT 0,active BOOLEAN NOT NULL DEFAULT TRUE,created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,UNIQUE(tenant_id,user_id))");
  $pdo->exec("ALTER TABLE customers ADD COLUMN IF NOT EXISTS reseller_id BIGINT REFERENCES reseller_profiles(id) ON DELETE SET NULL");
  $pdo->exec('CREATE INDEX IF NOT EXISTS idx_customers_tenant_reseller ON customers(tenant_id,reseller_id)');
  $pdo->exec("CREATE TABLE IF NOT EXISTS reseller_ledger (id BIGSERIAL PRIMARY KEY,tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,reseller_id BIGINT NOT NULL REFERENCES reseller_profiles(id) ON DELETE CASCADE,type VARCHAR(30) NOT NULL,amount BIGINT NOT NULL,reference_type VARCHAR(50),reference_id BIGINT,note TEXT,created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP)");
  $pdo->exec('CREATE INDEX IF NOT EXISTS idx_reseller_ledger_reseller_created ON reseller_ledger(reseller_id,created_at DESC)');
 }
 public function down(PDO $pdo):void{$pdo->exec('DROP TABLE IF EXISTS reseller_ledger');$pdo->exec('ALTER TABLE customers DROP COLUMN IF EXISTS reseller_id');$pdo->exec('DROP TABLE IF EXISTS reseller_profiles');}
};
