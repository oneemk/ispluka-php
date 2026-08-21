<?php

declare(strict_types=1);

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS pppoe_reconciliation_findings (
            id BIGSERIAL PRIMARY KEY,
            tenant_id BIGINT NOT NULL,
            router_id BIGINT NOT NULL,
            username VARCHAR(191) NOT NULL,
            finding_type VARCHAR(64) NOT NULL,
            severity VARCHAR(20) NOT NULL DEFAULT 'warning',
            message TEXT NOT NULL,
            details JSONB NOT NULL DEFAULT '{}'::jsonb,
            status VARCHAR(20) NOT NULL DEFAULT 'open',
            first_seen_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_seen_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolved_at TIMESTAMPTZ NULL,
            CONSTRAINT uq_pppoe_reconciliation_finding
                UNIQUE (tenant_id, router_id, username, finding_type),
            CONSTRAINT chk_pppoe_reconciliation_severity
                CHECK (severity IN ('critical', 'high', 'warning', 'info')),
            CONSTRAINT chk_pppoe_reconciliation_status
                CHECK (status IN ('open', 'resolved'))
        )");

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_pppoe_reconciliation_tenant_status
            ON pppoe_reconciliation_findings(tenant_id, status, last_seen_at DESC)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_pppoe_reconciliation_router_status
            ON pppoe_reconciliation_findings(tenant_id, router_id, status, last_seen_at DESC)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_pppoe_reconciliation_severity
            ON pppoe_reconciliation_findings(tenant_id, severity, last_seen_at DESC)');
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS pppoe_reconciliation_findings');
    }
};
