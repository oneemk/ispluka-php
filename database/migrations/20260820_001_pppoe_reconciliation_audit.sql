CREATE TABLE IF NOT EXISTS pppoe_reconciliation_findings (
 id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 tenant_id BIGINT NOT NULL,
 router_id BIGINT NOT NULL,
 username VARCHAR(191) NOT NULL,
 finding_type VARCHAR(60) NOT NULL,
 severity VARCHAR(20) NOT NULL DEFAULT 'warning' CHECK (severity IN ('critical','high','warning','info')),
 message VARCHAR(500) NOT NULL,
 details JSONB NOT NULL DEFAULT '{}'::jsonb,
 status VARCHAR(20) NOT NULL DEFAULT 'open' CHECK (status IN ('open','resolved')),
 first_seen_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
 last_seen_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
 resolved_at TIMESTAMPTZ NULL,
 UNIQUE (tenant_id, router_id, username, finding_type)
);
CREATE INDEX IF NOT EXISTS idx_pppoe_findings_tenant_status ON pppoe_reconciliation_findings(tenant_id, status);
CREATE INDEX IF NOT EXISTS idx_pppoe_findings_tenant_router ON pppoe_reconciliation_findings(tenant_id, router_id, status);
CREATE INDEX IF NOT EXISTS idx_pppoe_findings_last_seen ON pppoe_reconciliation_findings(tenant_id, last_seen_at DESC);

CREATE TABLE IF NOT EXISTS pppoe_enforcement_log (
 id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 tenant_id BIGINT NOT NULL,
 router_id BIGINT,
 username VARCHAR(191),
 action VARCHAR(80) NOT NULL,
 status VARCHAR(20) NOT NULL CHECK (status IN ('success','failed','mismatch')),
 message TEXT,
 details JSONB NOT NULL DEFAULT '{}'::jsonb,
 created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_pppoe_enforcement_tenant_created ON pppoe_enforcement_log(tenant_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_pppoe_enforcement_tenant_status ON pppoe_enforcement_log(tenant_id, status);
CREATE INDEX IF NOT EXISTS idx_pppoe_enforcement_router_username ON pppoe_enforcement_log(tenant_id, router_id, username, created_at DESC);
