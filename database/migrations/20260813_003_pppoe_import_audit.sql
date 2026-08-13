CREATE TABLE IF NOT EXISTS pppoe_import_audit (
 id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 tenant_id BIGINT NOT NULL,
 router_id BIGINT NOT NULL,
 username VARCHAR(191) NOT NULL,
 action VARCHAR(40) NOT NULL,
 mapped_customer_id BIGINT NULL,
 actor_id BIGINT NULL,
 details JSONB NULL,
 occurred_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_pppoe_import_audit_tenant_time ON pppoe_import_audit(tenant_id,occurred_at DESC);
CREATE INDEX IF NOT EXISTS idx_pppoe_import_audit_username ON pppoe_import_audit(tenant_id,router_id,username);
