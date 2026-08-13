CREATE TABLE IF NOT EXISTS pppoe_activity_state (
 id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 tenant_id BIGINT NOT NULL,
 router_id BIGINT NOT NULL,
 username VARCHAR(191) NOT NULL,
 online BOOLEAN NOT NULL DEFAULT FALSE,
 active_ip VARCHAR(64) NULL,
 last_seen_at TIMESTAMPTZ NULL,
 uptime_seconds BIGINT NULL,
 rx_bytes BIGINT NULL,
 tx_bytes BIGINT NULL,
 rx_rate_bps BIGINT NULL,
 tx_rate_bps BIGINT NULL,
 stale BOOLEAN NOT NULL DEFAULT FALSE,
 updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE (tenant_id, router_id, username)
);
CREATE INDEX IF NOT EXISTS idx_pppoe_activity_tenant_online ON pppoe_activity_state(tenant_id, online);
CREATE INDEX IF NOT EXISTS idx_pppoe_activity_tenant_last_seen ON pppoe_activity_state(tenant_id, last_seen_at);
CREATE TABLE IF NOT EXISTS pppoe_usage_hourly (
 id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 tenant_id BIGINT NOT NULL,
 router_id BIGINT NOT NULL,
 username VARCHAR(191) NOT NULL,
 bucket_start TIMESTAMPTZ NOT NULL,
 rx_bytes BIGINT NOT NULL DEFAULT 0,
 tx_bytes BIGINT NOT NULL DEFAULT 0,
 online_seconds BIGINT NOT NULL DEFAULT 0,
 samples INTEGER NOT NULL DEFAULT 0,
 UNIQUE (tenant_id, router_id, username, bucket_start)
);
CREATE INDEX IF NOT EXISTS idx_pppoe_usage_hourly_tenant_bucket ON pppoe_usage_hourly(tenant_id, bucket_start);
