<?php

declare(strict_types=1);

use PDO;

return new class {
    public function up(PDO $pdo): void
    {
        if (!$this->tableExists($pdo, 'routers')) {
            throw new RuntimeException('routers table does not exist. Run the core schema migration first.');
        }

        $pdo->beginTransaction();
        try {
            $this->renameLegacyPasswordColumn($pdo);
            $this->addColumnIfMissing($pdo, 'code', "VARCHAR(60)");
            $this->addColumnIfMissing($pdo, 'api_ssl_port', "INTEGER");
            $this->addColumnIfMissing($pdo, 'verify_ssl', "BOOLEAN NOT NULL DEFAULT true");
            $this->addColumnIfMissing($pdo, 'metadata', "JSONB NOT NULL DEFAULT '{}'::jsonb");

            $pdo->exec("UPDATE routers SET code = 'router-' || id WHERE code IS NULL OR BTRIM(code) = ''");
            $pdo->exec('ALTER TABLE routers ALTER COLUMN code SET NOT NULL');
            $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_routers_tenant_code ON routers(tenant_id, code)");

            if (!$this->columnExists($pdo, 'encrypted_password')) {
                throw new RuntimeException('routers.encrypted_password is missing after reconciliation.');
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function down(PDO $pdo): void
    {
        // This migration is intentionally non-destructive. Router credentials and
        // metadata must never be removed automatically during a rollback.
    }

    private function renameLegacyPasswordColumn(PDO $pdo): void
    {
        $canonical = $this->columnExists($pdo, 'encrypted_password');
        $legacy = $this->columnExists($pdo, 'password_encrypted');

        if (!$canonical && $legacy) {
            $pdo->exec('ALTER TABLE routers RENAME COLUMN password_encrypted TO encrypted_password');
        }
    }

    private function addColumnIfMissing(PDO $pdo, string $column, string $definition): void
    {
        if (!$this->columnExists($pdo, $column)) {
            $pdo->exec(sprintf('ALTER TABLE routers ADD COLUMN %s %s', $column, $definition));
        }
    }

    private function tableExists(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare("SELECT EXISTS (
            SELECT 1 FROM information_schema.tables
            WHERE table_schema = current_schema() AND table_name = :table
        )");
        $stmt->execute([':table' => $table]);
        return (bool) $stmt->fetchColumn();
    }

    private function columnExists(PDO $pdo, string $column): bool
    {
        $stmt = $pdo->prepare("SELECT EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_schema = current_schema()
              AND table_name = 'routers'
              AND column_name = :column
        )");
        $stmt->execute([':column' => $column]);
        return (bool) $stmt->fetchColumn();
    }
};
