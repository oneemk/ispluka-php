CREATE TABLE IF NOT EXISTS pppoe_enforcement_log (
 id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 tenant_id BIGINT NOT NULL,
 router_id BIGINT NOT NULL,
 username VARCHAR(191) NOT NULL,
 action VARCHAR(40) NOT NULL,
 original_profile VARCHAR(191) NULL,
 target_profile VARCHAR(191) NULL,
 reason VARCHAR(80) NOT NULL,
 status VARCHAR(20) NOT NULL,
 error_message TEXT NULL,
 actor_id BIGINT NULL,
 created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_pppoe_enforcement_log_lookup ON pppoe_enforcement_log(tenant_id,router_id,username,created_at DESC);
