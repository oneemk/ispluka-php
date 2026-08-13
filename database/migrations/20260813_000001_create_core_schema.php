<?php

declare(strict_types=1);

use Ispluka\Database\Migrations\MigrationInterface;
use PDO;

return new class implements MigrationInterface
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE tenants (
            id BIGSERIAL PRIMARY KEY,
            name VARCHAR(160) NOT NULL,
            code VARCHAR(50) NOT NULL UNIQUE,
            legal_name VARCHAR(200),
            status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','suspended','trial','closed')),
            timezone VARCHAR(64) NOT NULL DEFAULT 'Asia/Dhaka',
            currency CHAR(3) NOT NULL DEFAULT 'BDT',
            settings JSONB NOT NULL DEFAULT '{}'::jsonb,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            deleted_at TIMESTAMPTZ
        )");

        $pdo->exec("CREATE TABLE roles (
            id BIGSERIAL PRIMARY KEY,
            tenant_id BIGINT REFERENCES tenants(id) ON DELETE CASCADE,
            name VARCHAR(60) NOT NULL,
            code VARCHAR(60) NOT NULL,
            description VARCHAR(255),
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (tenant_id, code)
        )");

        $pdo->exec("CREATE TABLE permissions (
            id BIGSERIAL PRIMARY KEY,
            code VARCHAR(120) NOT NULL UNIQUE,
            description VARCHAR(255),
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE users (
            id BIGSERIAL PRIMARY KEY,
            tenant_id BIGINT REFERENCES tenants(id) ON DELETE RESTRICT,
            name VARCHAR(160) NOT NULL,
            email VARCHAR(190),
            phone VARCHAR(30),
            password_hash VARCHAR(255) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive','locked')),
            email_verified_at TIMESTAMPTZ,
            last_login_at TIMESTAMPTZ,
            password_changed_at TIMESTAMPTZ,
            failed_login_attempts INTEGER NOT NULL DEFAULT 0 CHECK (failed_login_attempts >= 0),
            locked_until TIMESTAMPTZ,
            remember_token_hash VARCHAR(255),
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            deleted_at TIMESTAMPTZ
        )");

        $pdo->exec("CREATE TABLE user_roles (
            user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            role_id BIGINT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
            assigned_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, role_id)
        )");

        $pdo->exec("CREATE TABLE role_permissions (
            role_id BIGINT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
            permission_id BIGINT NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
            PRIMARY KEY (role_id, permission_id)
        )");

        $pdo->exec("CREATE TABLE resellers (
            id BIGSERIAL PRIMARY KEY,
            tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE RESTRICT,
            user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
            code VARCHAR(50) NOT NULL,
            name VARCHAR(160) NOT NULL,
            phone VARCHAR(30),
            commission_type VARCHAR(20) NOT NULL DEFAULT 'percentage' CHECK (commission_type IN ('percentage','fixed')),
            commission_value NUMERIC(12,2) NOT NULL DEFAULT 0 CHECK (commission_value >= 0),
            status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive')),
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            deleted_at TIMESTAMPTZ,
            UNIQUE (tenant_id, code)
        )");

        $pdo->exec("CREATE TABLE customers (
            id BIGSERIAL PRIMARY KEY,
            tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE RESTRICT,
            reseller_id BIGINT REFERENCES resellers(id) ON DELETE SET NULL,
            customer_code VARCHAR(80) NOT NULL,
            name VARCHAR(160) NOT NULL,
            phone VARCHAR(30),
            email VARCHAR(190),
            nid VARCHAR(80),
            address TEXT,
            area VARCHAR(120),
            latitude NUMERIC(10,7),
            longitude NUMERIC(10,7),
            status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive','suspended','closed')),
            billing_day SMALLINT NOT NULL DEFAULT 1 CHECK (billing_day BETWEEN 1 AND 28),
            credit_limit NUMERIC(14,2) NOT NULL DEFAULT 0 CHECK (credit_limit >= 0),
            balance NUMERIC(14,2) NOT NULL DEFAULT 0,
            metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            deleted_at TIMESTAMPTZ,
            UNIQUE (tenant_id, customer_code)
        )");

        $pdo->exec("CREATE TABLE packages (
            id BIGSERIAL PRIMARY KEY,
            tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE RESTRICT,
            name VARCHAR(120) NOT NULL,
            code VARCHAR(60) NOT NULL,
            description TEXT,
            service_type VARCHAR(20) NOT NULL DEFAULT 'pppoe' CHECK (service_type IN ('pppoe','hotspot','both')),
            download_kbps INTEGER NOT NULL CHECK (download_kbps > 0),
            upload_kbps INTEGER NOT NULL CHECK (upload_kbps > 0),
            price NUMERIC(12,2) NOT NULL CHECK (price >= 0),
            billing_period VARCHAR(20) NOT NULL DEFAULT 'monthly' CHECK (billing_period IN ('daily','weekly','monthly','custom')),
            validity_days INTEGER CHECK (validity_days IS NULL OR validity_days > 0),
            status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive')),
            settings JSONB NOT NULL DEFAULT '{}'::jsonb,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (tenant_id, code)
        )");

        $pdo->exec("CREATE TABLE routers (
            id BIGSERIAL PRIMARY KEY,
            tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE RESTRICT,
            name VARCHAR(120) NOT NULL,
            code VARCHAR(60) NOT NULL,
            host VARCHAR(255) NOT NULL,
            api_port INTEGER NOT NULL DEFAULT 8728 CHECK (api_port BETWEEN 1 AND 65535),
            api_ssl_port INTEGER CHECK (api_ssl_port IS NULL OR api_ssl_port BETWEEN 1 AND 65535),
            username VARCHAR(120) NOT NULL,
            encrypted_password TEXT NOT NULL,
            verify_ssl BOOLEAN NOT NULL DEFAULT true,
            status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive','maintenance')),
            last_seen_at TIMESTAMPTZ,
            last_error TEXT,
            metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (tenant_id, code)
        )");

        $pdo->exec("CREATE TABLE customer_services (
            id BIGSERIAL PRIMARY KEY,
            tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE RESTRICT,
            customer_id BIGINT NOT NULL REFERENCES customers(id) ON DELETE RESTRICT,
            package_id BIGINT REFERENCES packages(id) ON DELETE RESTRICT,
            router_id BIGINT REFERENCES routers(id) ON DELETE SET NULL,
            service_type VARCHAR(20) NOT NULL CHECK (service_type IN ('pppoe','hotspot')),
            username VARCHAR(160),
            secret_hash VARCHAR(255),
            mac_address VARCHAR(32),
            ip_address INET,
            start_date DATE NOT NULL,
            next_billing_date DATE,
            status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','suspended','expired','terminated')),
            auto_suspend BOOLEAN NOT NULL DEFAULT true,
            settings JSONB NOT NULL DEFAULT '{}'::jsonb,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE invoices (
            id BIGSERIAL PRIMARY KEY,
            tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE RESTRICT,
            customer_id BIGINT NOT NULL REFERENCES customers(id) ON DELETE RESTRICT,
            invoice_number VARCHAR(80) NOT NULL,
            issue_date DATE NOT NULL,
            due_date DATE NOT NULL,
            subtotal NUMERIC(14,2) NOT NULL DEFAULT 0 CHECK (subtotal >= 0),
            discount NUMERIC(14,2) NOT NULL DEFAULT 0 CHECK (discount >= 0),
            tax NUMERIC(14,2) NOT NULL DEFAULT 0 CHECK (tax >= 0),
            total NUMERIC(14,2) NOT NULL DEFAULT 0 CHECK (total >= 0),
            paid_amount NUMERIC(14,2) NOT NULL DEFAULT 0 CHECK (paid_amount >= 0),
            status VARCHAR(20) NOT NULL DEFAULT 'unpaid' CHECK (status IN ('draft','unpaid','partial','paid','void','overdue')),
            metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (tenant_id, invoice_number)
        )");

        $pdo->exec("CREATE TABLE invoice_items (
            id BIGSERIAL PRIMARY KEY,
            invoice_id BIGINT NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
            service_id BIGINT REFERENCES customer_services(id) ON DELETE SET NULL,
            description VARCHAR(255) NOT NULL,
            quantity NUMERIC(12,3) NOT NULL DEFAULT 1 CHECK (quantity > 0),
            unit_price NUMERIC(14,2) NOT NULL DEFAULT 0 CHECK (unit_price >= 0),
            discount NUMERIC(14,2) NOT NULL DEFAULT 0 CHECK (discount >= 0),
            tax NUMERIC(14,2) NOT NULL DEFAULT 0 CHECK (tax >= 0),
            total NUMERIC(14,2) NOT NULL CHECK (total >= 0),
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE payments (
            id BIGSERIAL PRIMARY KEY,
            tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE RESTRICT,
            customer_id BIGINT NOT NULL REFERENCES customers(id) ON DELETE RESTRICT,
            invoice_id BIGINT REFERENCES invoices(id) ON DELETE SET NULL,
            reference VARCHAR(120) NOT NULL,
            method VARCHAR(30) NOT NULL CHECK (method IN ('cash','bank','bkash','nagad','card','other')),
            gateway VARCHAR(60),
            amount NUMERIC(14,2) NOT NULL CHECK (amount > 0),
            status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','completed','failed','refunded','cancelled')),
            gateway_transaction_id VARCHAR(190),
            paid_at TIMESTAMPTZ,
            metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (tenant_id, reference)
        )");

        $pdo->exec("CREATE TABLE payment_gateways (
            id BIGSERIAL PRIMARY KEY,
            tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
            code VARCHAR(60) NOT NULL,
            name VARCHAR(120) NOT NULL,
            enabled BOOLEAN NOT NULL DEFAULT false,
            encrypted_config TEXT,
            settings JSONB NOT NULL DEFAULT '{}'::jsonb,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (tenant_id, code)
        )");

        $pdo->exec("CREATE TABLE audit_logs (
            id BIGSERIAL PRIMARY KEY,
            tenant_id BIGINT REFERENCES tenants(id) ON DELETE SET NULL,
            user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
            action VARCHAR(100) NOT NULL,
            auditable_type VARCHAR(120),
            auditable_id BIGINT,
            ip_address INET,
            user_agent TEXT,
            request_id VARCHAR(80),
            old_values JSONB,
            new_values JSONB,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE notifications (
            id BIGSERIAL PRIMARY KEY,
            tenant_id BIGINT REFERENCES tenants(id) ON DELETE CASCADE,
            user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
            customer_id BIGINT REFERENCES customers(id) ON DELETE CASCADE,
            channel VARCHAR(30) NOT NULL CHECK (channel IN ('database','email','sms','whatsapp','push')),
            type VARCHAR(80) NOT NULL,
            subject VARCHAR(255),
            body TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','sent','failed','cancelled')),
            attempts SMALLINT NOT NULL DEFAULT 0 CHECK (attempts >= 0),
            sent_at TIMESTAMPTZ,
            last_error TEXT,
            payload JSONB NOT NULL DEFAULT '{}'::jsonb,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE jobs (
            id BIGSERIAL PRIMARY KEY,
            tenant_id BIGINT REFERENCES tenants(id) ON DELETE CASCADE,
            queue VARCHAR(80) NOT NULL DEFAULT 'default',
            job_type VARCHAR(120) NOT NULL,
            payload JSONB NOT NULL DEFAULT '{}'::jsonb,
            status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','processing','completed','failed','cancelled')),
            attempts SMALLINT NOT NULL DEFAULT 0 CHECK (attempts >= 0),
            available_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reserved_at TIMESTAMPTZ,
            completed_at TIMESTAMPTZ,
            last_error TEXT,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE tenant_subscriptions (
            id BIGSERIAL PRIMARY KEY,
            tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
            plan_code VARCHAR(60) NOT NULL,
            amount NUMERIC(12,2) NOT NULL CHECK (amount >= 0),
            starts_at TIMESTAMPTZ NOT NULL,
            ends_at TIMESTAMPTZ,
            status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('trial','active','past_due','suspended','cancelled','expired')),
            metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");

        $indexes = [
            'CREATE INDEX idx_users_tenant_status ON users (tenant_id, status)',
            'CREATE UNIQUE INDEX uq_users_tenant_email ON users (tenant_id, lower(email)) WHERE email IS NOT NULL AND deleted_at IS NULL',
            'CREATE INDEX idx_customers_tenant_status ON customers (tenant_id, status)',
            'CREATE INDEX idx_customers_tenant_phone ON customers (tenant_id, phone)',
            'CREATE INDEX idx_customers_tenant_reseller ON customers (tenant_id, reseller_id)',
            'CREATE INDEX idx_resellers_tenant_status ON resellers (tenant_id, status)',
            'CREATE INDEX idx_packages_tenant_status ON packages (tenant_id, status)',
            'CREATE INDEX idx_routers_tenant_status ON routers (tenant_id, status)',
            'CREATE INDEX idx_services_tenant_status ON customer_services (tenant_id, status)',
            'CREATE INDEX idx_services_customer ON customer_services (tenant_id, customer_id)',
            'CREATE INDEX idx_services_router ON customer_services (tenant_id, router_id)',
            'CREATE INDEX idx_invoices_customer_status ON invoices (tenant_id, customer_id, status)',
            'CREATE INDEX idx_invoices_due_date ON invoices (tenant_id, due_date, status)',
            'CREATE INDEX idx_invoice_items_invoice ON invoice_items (invoice_id)',
            'CREATE INDEX idx_payments_customer_date ON payments (tenant_id, customer_id, created_at DESC)',
            'CREATE INDEX idx_payments_invoice ON payments (tenant_id, invoice_id)',
            'CREATE UNIQUE INDEX uq_payments_gateway_txn ON payments (tenant_id, gateway, gateway_transaction_id) WHERE gateway_transaction_id IS NOT NULL',
            'CREATE INDEX idx_audit_tenant_created ON audit_logs (tenant_id, created_at DESC)',
            'CREATE INDEX idx_audit_user_created ON audit_logs (user_id, created_at DESC)',
            'CREATE INDEX idx_notifications_pending ON notifications (status, created_at)',
            'CREATE INDEX idx_jobs_claimable ON jobs (status, available_at, id)',
            'CREATE INDEX idx_jobs_tenant ON jobs (tenant_id, status, created_at)',
            'CREATE INDEX idx_tenant_subscriptions_status ON tenant_subscriptions (tenant_id, status, ends_at)',
        ];

        foreach ($indexes as $sql) {
            $pdo->exec($sql);
        }
    }

    public function down(PDO $pdo): void
    {
        foreach ([
            'tenant_subscriptions', 'jobs', 'notifications', 'audit_logs',
            'payment_gateways', 'payments', 'invoice_items', 'invoices',
            'customer_services', 'routers', 'packages', 'customers', 'resellers',
            'role_permissions', 'user_roles', 'users', 'permissions', 'roles', 'tenants',
        ] as $table) {
            $pdo->exec('DROP TABLE IF EXISTS ' . $table . ' CASCADE');
        }
    }
};
