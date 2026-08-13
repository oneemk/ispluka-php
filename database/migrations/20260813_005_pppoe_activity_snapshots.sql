CREATE TABLE IF NOT EXISTS pppoe_activity_snapshots (
 id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 tenant_id BIGINT NOT NULL,
 router_id BIGINT NOT NULL,
 username VARCHAR(191) NOT NULL,
 enabled BOOLEAN NOT NULL,
 active BOOLEAN NOT NULL,
 active_ip VARCHAR(64) NULL,
 caller_id VARCHAR(191) NULL,
 uptime_seconds BIGINT NULL,
 last_seen_at TIMESTAMPTZ NULL,
 rx_bytes BIGINT NULL,
 tx_bytes BIGINT NULL,
 observed_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE(tenant_id,router_id,username)
);
CREATE INDEX IF NOT EXISTS idx_pppoe_activity_tenant_lastseen ON pppoe_activity_snapshots(tenant_id,last_seen_at);
CREATE INDEX IF NOT EXISTS idx_pppoe_activity_tenant_active ON pppoe_activity_snapshots(tenant_id,active);
