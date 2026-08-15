<?php

declare(strict_types=1);


return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS hotspot_profiles (id BIGSERIAL PRIMARY KEY,tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,name VARCHAR(120) NOT NULL,code VARCHAR(60) NOT NULL,validity_expression VARCHAR(80) NOT NULL,validity_seconds BIGINT NOT NULL,activation_mode VARCHAR(30) NOT NULL DEFAULT 'first_login',rate_limit VARCHAR(120),data_limit_bytes BIGINT,session_limit_seconds BIGINT,shared_users SMALLINT NOT NULL DEFAULT 1,status VARCHAR(20) NOT NULL DEFAULT 'active',created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,UNIQUE(tenant_id,code),CHECK(validity_seconds>0),CHECK(activation_mode='first_login'))");
        $pdo->exec("CREATE TABLE IF NOT EXISTS hotspot_users (id BIGSERIAL PRIMARY KEY,tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,profile_id BIGINT NOT NULL REFERENCES hotspot_profiles(id) ON DELETE RESTRICT,router_id BIGINT NULL REFERENCES routers(id) ON DELETE SET NULL,username VARCHAR(120) NOT NULL,password_ciphertext TEXT NOT NULL,status VARCHAR(20) NOT NULL DEFAULT 'unused',activated_at TIMESTAMPTZ NULL,expires_at TIMESTAMPTZ NULL,mac_address VARCHAR(32),notes TEXT,created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,UNIQUE(tenant_id,username),CHECK(status IN ('unused','active','expired','disabled')))");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_hotspot_users_expiry ON hotspot_users(tenant_id,status,expires_at)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS hotspot_router_time_checks (id BIGSERIAL PRIMARY KEY,tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,router_id BIGINT NOT NULL REFERENCES routers(id) ON DELETE CASCADE,router_time TIMESTAMPTZ NOT NULL,server_time TIMESTAMPTZ NOT NULL,difference_seconds BIGINT NOT NULL,tolerance_seconds INTEGER NOT NULL,warning BOOLEAN NOT NULL,checked_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP)");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS hotspot_router_time_checks');
        $pdo->exec('DROP TABLE IF EXISTS hotspot_users');
        $pdo->exec('DROP TABLE IF EXISTS hotspot_profiles');
    }
};
