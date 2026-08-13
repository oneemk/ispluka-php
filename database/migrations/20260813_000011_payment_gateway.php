<?php

declare(strict_types=1);
use PDO;
return new class {
 public function up(PDO $pdo):void{$pdo->exec("ALTER TABLE payments ADD COLUMN IF NOT EXISTS gateway VARCHAR(40); ALTER TABLE payments ADD COLUMN IF NOT EXISTS transaction_id VARCHAR(160); ALTER TABLE payments ADD COLUMN IF NOT EXISTS metadata JSONB NOT NULL DEFAULT '{}'::jsonb; ALTER TABLE payments ADD COLUMN IF NOT EXISTS paid_at TIMESTAMPTZ; ALTER TABLE payments ADD COLUMN IF NOT EXISTS updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP");$pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS uq_payments_tenant_transaction ON payments(tenant_id,transaction_id) WHERE transaction_id IS NOT NULL');$pdo->exec('CREATE INDEX IF NOT EXISTS idx_payments_tenant_gateway_status ON payments(tenant_id,gateway,status,paid_at)');}
 public function down(PDO $pdo):void{$pdo->exec('DROP INDEX IF EXISTS idx_payments_tenant_gateway_status');$pdo->exec('DROP INDEX IF EXISTS uq_payments_tenant_transaction');}
};
