<?php

declare(strict_types=1);

use Ispluka\Database\Migrations\MigrationInterface;
use PDO;

return new class implements MigrationInterface
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS username VARCHAR(80)");
        $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_users_tenant_username ON users (tenant_id, lower(username)) WHERE username IS NOT NULL AND deleted_at IS NULL");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP INDEX IF EXISTS uq_users_tenant_username');
        $pdo->exec('ALTER TABLE users DROP COLUMN IF EXISTS username');
    }
};
