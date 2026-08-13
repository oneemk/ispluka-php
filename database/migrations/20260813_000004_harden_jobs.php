<?php

declare(strict_types=1);

use PDO;

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN IF NOT EXISTS max_attempts INTEGER NOT NULL DEFAULT 5");
        $pdo->exec("ALTER TABLE jobs ADD COLUMN IF NOT EXISTS last_error TEXT NULL");
        $pdo->exec("ALTER TABLE jobs ADD COLUMN IF NOT EXISTS locked_at TIMESTAMPTZ NULL");
        $pdo->exec("ALTER TABLE jobs ADD COLUMN IF NOT EXISTS completed_at TIMESTAMPTZ NULL");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_jobs_claim ON jobs(status, available_at, id)");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP INDEX IF EXISTS idx_jobs_claim");
        $pdo->exec("ALTER TABLE jobs DROP COLUMN IF EXISTS completed_at");
        $pdo->exec("ALTER TABLE jobs DROP COLUMN IF EXISTS locked_at");
        $pdo->exec("ALTER TABLE jobs DROP COLUMN IF EXISTS last_error");
        $pdo->exec("ALTER TABLE jobs DROP COLUMN IF EXISTS max_attempts");
    }
};
