<?php

declare(strict_types=1);
return new class {
 public function up(PDO $pdo): void {
  $pdo->exec("CREATE TABLE IF NOT EXISTS invoices (id BIGSERIAL PRIMARY KEY, tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE, customer_id BIGINT NOT NULL REFERENCES customers(id) ON DELETE CASCADE, service_id BIGINT NULL REFERENCES customer_services(id) ON DELETE SET NULL, invoice_no VARCHAR(80) NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'issued', issue_date DATE NOT NULL DEFAULT CURRENT_DATE, due_date DATE NOT NULL, subtotal BIGINT NOT NULL DEFAULT 0, discount BIGINT NOT NULL DEFAULT 0, total BIGINT NOT NULL DEFAULT 0, paid_amount BIGINT NOT NULL DEFAULT 0, created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE(tenant_id,invoice_no))");
  $pdo->exec("CREATE TABLE IF NOT EXISTS invoice_items (id BIGSERIAL PRIMARY KEY, invoice_id BIGINT NOT NULL REFERENCES invoices(id) ON DELETE CASCADE, description VARCHAR(255) NOT NULL, quantity INTEGER NOT NULL DEFAULT 1 CHECK(quantity>0), unit_price BIGINT NOT NULL CHECK(unit_price>=0), amount BIGINT NOT NULL CHECK(amount>=0))");
  $pdo->exec("CREATE TABLE IF NOT EXISTS payment_allocations (id BIGSERIAL PRIMARY KEY, payment_id BIGINT NOT NULL REFERENCES payments(id) ON DELETE CASCADE, invoice_id BIGINT NOT NULL REFERENCES invoices(id) ON DELETE CASCADE, amount BIGINT NOT NULL CHECK(amount>0), UNIQUE(payment_id,invoice_id))");
  $pdo->exec('CREATE INDEX IF NOT EXISTS idx_invoices_tenant_customer_status ON invoices(tenant_id,customer_id,status,due_date)');
  $pdo->exec('CREATE INDEX IF NOT EXISTS idx_invoice_items_invoice ON invoice_items(invoice_id)');
  $pdo->exec('CREATE INDEX IF NOT EXISTS idx_payment_allocations_invoice ON payment_allocations(invoice_id)');
 }
 public function down(PDO $pdo): void { $pdo->exec('DROP TABLE IF EXISTS payment_allocations'); $pdo->exec('DROP TABLE IF EXISTS invoice_items'); $pdo->exec('DROP TABLE IF EXISTS invoices'); }
};
