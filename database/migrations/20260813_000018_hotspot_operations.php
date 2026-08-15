<?php

declare(strict_types=1);


return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS hotspot_sessions (id BIGSERIAL PRIMARY KEY,tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,hotspot_user_id BIGINT NOT NULL REFERENCES hotspot_users(id) ON DELETE CASCADE,router_id BIGINT NULL REFERENCES routers(id) ON DELETE SET NULL,mikrotik_session_id VARCHAR(160),client_ip INET,mac_address VARCHAR(32),started_at TIMESTAMPTZ NOT NULL,ended_at TIMESTAMPTZ NULL,bytes_in BIGINT NOT NULL DEFAULT 0,bytes_out BIGINT NOT NULL DEFAULT 0,status VARCHAR(20) NOT NULL DEFAULT 'active',created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_hotspot_sessions_active ON hotspot_sessions(tenant_id,status,hotspot_user_id)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS hotspot_ip_bindings (id BIGSERIAL PRIMARY KEY,tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,router_id BIGINT NOT NULL REFERENCES routers(id) ON DELETE CASCADE,address INET NOT NULL,mac_address VARCHAR(32),to_address INET,type VARCHAR(30) NOT NULL DEFAULT 'regular',comment VARCHAR(255),disabled BOOLEAN NOT NULL DEFAULT FALSE,created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP)");
        $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_hotspot_ip_bindings_identity ON hotspot_ip_bindings(tenant_id,router_id,address,COALESCE(mac_address,''))");
        $pdo->exec("CREATE TABLE IF NOT EXISTS hotspot_hosts (id BIGSERIAL PRIMARY KEY,tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,router_id BIGINT NOT NULL REFERENCES routers(id) ON DELETE CASCADE,address INET,mac_address VARCHAR(32),uptime_seconds BIGINT NOT NULL DEFAULT 0,bytes_in BIGINT NOT NULL DEFAULT 0,bytes_out BIGINT NOT NULL DEFAULT 0,last_seen_at TIMESTAMPTZ,created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS hotspot_walled_garden (id BIGSERIAL PRIMARY KEY,tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,router_id BIGINT NOT NULL REFERENCES routers(id) ON DELETE CASCADE,dst_host VARCHAR(255),dst_path VARCHAR(255),action VARCHAR(30) NOT NULL DEFAULT 'allow',comment VARCHAR(255),disabled BOOLEAN NOT NULL DEFAULT FALSE,created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS hotspot_address_lists (id BIGSERIAL PRIMARY KEY,tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,router_id BIGINT NOT NULL REFERENCES routers(id) ON DELETE CASCADE,list_name VARCHAR(120) NOT NULL,address CIDR NOT NULL,timeout_seconds BIGINT,comment VARCHAR(255),disabled BOOLEAN NOT NULL DEFAULT FALSE,created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS hotspot_operation_logs (id BIGSERIAL PRIMARY KEY,tenant_id BIGINT NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,router_id BIGINT NULL REFERENCES routers(id) ON DELETE SET NULL,hotspot_user_id BIGINT NULL REFERENCES hotspot_users(id) ON DELETE SET NULL,actor_user_id BIGINT NULL,action VARCHAR(80) NOT NULL,status VARCHAR(20) NOT NULL,details JSONB,created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP)");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS hotspot_operation_logs');
        $pdo->exec('DROP TABLE IF EXISTS hotspot_address_lists');
        $pdo->exec('DROP TABLE IF EXISTS hotspot_walled_garden');
        $pdo->exec('DROP TABLE IF EXISTS hotspot_hosts');
        $pdo->exec('DROP TABLE IF EXISTS hotspot_ip_bindings');
        $pdo->exec('DROP TABLE IF EXISTS hotspot_sessions');
    }
};
