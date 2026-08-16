<?php

declare(strict_types=1);

use PDO;

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'routers' AND column_name = 'connection_method'
    ) THEN
        ALTER TABLE routers ADD COLUMN connection_method VARCHAR(10) NOT NULL DEFAULT 'api';
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'routers' AND column_name = 'ssh_port'
    ) THEN
        ALTER TABLE routers ADD COLUMN ssh_port INTEGER NOT NULL DEFAULT 22;
    END IF;
END $$;

UPDATE routers
SET connection_method = 'api'
WHERE connection_method IS NULL OR connection_method NOT IN ('api', 'ssh');

ALTER TABLE routers DROP CONSTRAINT IF EXISTS routers_connection_method_check;
ALTER TABLE routers ADD CONSTRAINT routers_connection_method_check CHECK (connection_method IN ('api', 'ssh'));
ALTER TABLE routers DROP CONSTRAINT IF EXISTS routers_ssh_port_check;
ALTER TABLE routers ADD CONSTRAINT routers_ssh_port_check CHECK (ssh_port BETWEEN 1 AND 65535);
SQL);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
ALTER TABLE routers DROP CONSTRAINT IF EXISTS routers_ssh_port_check;
ALTER TABLE routers DROP CONSTRAINT IF EXISTS routers_connection_method_check;
ALTER TABLE routers DROP COLUMN IF EXISTS ssh_port;
ALTER TABLE routers DROP COLUMN IF EXISTS connection_method;
SQL);
    }
};
