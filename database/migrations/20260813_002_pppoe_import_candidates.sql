CREATE TABLE IF NOT EXISTS pppoe_import_candidates (
 id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 tenant_id BIGINT NOT NULL,
 router_id BIGINT NOT NULL,
 username VARCHAR(191) NOT NULL,
 profile VARCHAR(191) NULL,
 active_ip VARCHAR(64) NULL,
 caller_id VARCHAR(191) NULL,
 mapped_customer_id BIGINT NULL,
 status VARCHAR(24) NOT NULL DEFAULT 'pending',
 completed_at TIMESTAMPTZ NULL,
 created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE (tenant_id, router_id, username)
);
CREATE INDEX IF NOT EXISTS idx_pppoe_import_candidates_tenant_status ON pppoe_import_candidates(tenant_id,status);
CREATE INDEX IF NOT EXISTS idx_pppoe_import_candidates_customer ON pppoe_import_candidates(mapped_customer_id);
