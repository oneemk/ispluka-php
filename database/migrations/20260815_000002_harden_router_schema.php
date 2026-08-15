<?php

declare(strict_types=1);

use PDO;

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'routers' AND column_name = 'password_encrypted'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'routers' AND column_name = 'encrypted_password'
    ) THEN
        ALTER TABLE routers RENAME COLUMN password_encrypted TO encrypted_password;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'routers' AND column_name = 'code'
    ) THEN
        ALTER TABLE routers ADD COLUMN code VARCHAR(80);
        UPDATE routers SET code = 'RTR-' || id::text WHERE code IS NULL;
        ALTER TABLE routers ALTER COLUMN code SET NOT NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'routers' AND column_name = 'api_ssl_port'
    ) THEN
        ALTER TABLE routers ADD COLUMN api_ssl_port INTEGER NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'routers' AND column_name = 'verify_ssl'
    ) THEN
        ALTER TABLE routers ADD COLUMN verify_ssl BOOLEAN NOT NULL DEFAULT TRUE;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'routers' AND column_name = 'metadata'
    ) THEN
        ALTER TABLE routers ADD COLUMN metadata JSONB NOT NULL DEFAULT '{}'::jsonb;
    END IF;
END $$;

ALTER TABLE routers ALTER COLUMN status SET DEFAULT 'offline';
UPDATE routers SET status = 'offline' WHERE status IS NULL OR status = 'active';

CREATE UNIQUE INDEX IF NOT EXISTS uq_routers_tenant_code ON routers(tenant_id, code);
CREATE INDEX IF NOT EXISTS idx_routers_tenant_last_seen ON routers(tenant_id, last_seen_at DESC);
SQL);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
DROP INDEX IF EXISTS idx_routers_tenant_last_seen;
DROP INDEX IF EXISTS uq_routers_tenant_code;
ALTER TABLE routers
    DROP COLUMN IF EXISTS metadata,
    DROP COLUMN IF EXISTS verify_ssl,
    DROP COLUMN IF EXISTS api_ssl_port;
SQL);
    }
};
