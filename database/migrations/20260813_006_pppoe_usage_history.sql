CREATE TABLE IF NOT EXISTS pppoe_usage_history (
 id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 tenant_id BIGINT NOT NULL,
 router_id BIGINT NOT NULL,
 username VARCHAR(191) NOT NULL,
 sample_date DATE NOT NULL,
 rx_bytes BIGINT NOT NULL DEFAULT 0,
 tx_bytes BIGINT NOT NULL DEFAULT 0,
 online_seconds BIGINT NOT NULL DEFAULT 0,
 samples INTEGER NOT NULL DEFAULT 0,
 updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE(tenant_id,router_id,username,sample_date)
);
CREATE INDEX IF NOT EXISTS idx_pppoe_usage_history_lookup ON pppoe_usage_history(tenant_id,username,sample_date DESC);
CREATE INDEX IF NOT EXISTS idx_pppoe_usage_history_retention ON pppoe_usage_history(sample_date);
