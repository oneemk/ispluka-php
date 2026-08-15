<?php

declare(strict_types=1);

return new class {
    public function up(PDO $pdo): void
    {
        // Router health is independent from administrative lifecycle state.
        // The application uses online/offline/unknown for real connection state.
        $pdo->exec("ALTER TABLE routers DROP CONSTRAINT IF EXISTS routers_status_check");
        $pdo->exec("ALTER TABLE routers ADD CONSTRAINT routers_status_check CHECK (status IN ('online','offline','unknown','active','inactive','maintenance'))");
        $pdo->exec("ALTER TABLE routers ALTER COLUMN status SET DEFAULT 'unknown'");

        // Normalize legacy administrative values to the runtime health model.
        $pdo->exec("UPDATE routers SET status = 'unknown' WHERE status IS NULL OR status IN ('active','inactive','maintenance')");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("UPDATE routers SET status = 'active' WHERE status IN ('online','offline','unknown')");
        $pdo->exec("ALTER TABLE routers DROP CONSTRAINT IF EXISTS routers_status_check");
        $pdo->exec("ALTER TABLE routers ADD CONSTRAINT routers_status_check CHECK (status IN ('active','inactive','maintenance'))");
        $pdo->exec("ALTER TABLE routers ALTER COLUMN status SET DEFAULT 'active'");
    }
};
