<?php

declare(strict_types=1);

namespace Ispluka\Database\Migrations;

use PDO;
use RuntimeException;

final class MigrationRunner
{
    private const TABLE = 'schema_migrations';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function ensureRepository(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS ' . self::TABLE . ' (' .
            'id BIGSERIAL PRIMARY KEY,' .
            'migration VARCHAR(255) NOT NULL UNIQUE,' .
            'batch INTEGER NOT NULL,' .
            'executed_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP' .
            ')'
        );
    }

    /** @param array<string, object> $migrations */
    public function migrate(array $migrations): array
    {
        $this->ensureRepository();
        $applied = $this->appliedNames();
        $batch = $this->nextBatch();
        $executed = [];

        foreach ($migrations as $name => $migration) {
            if (!is_object($migration) || !method_exists($migration, 'up')) {
                throw new RuntimeException('Invalid migration: ' . (string) $name);
            }

            $migrationName = (string) $name;
            if (isset($applied[$migrationName])) {
                continue;
            }

            $this->pdo->beginTransaction();
            try {
                $migration->up($this->pdo);
                $statement = $this->pdo->prepare(
                    'INSERT INTO ' . self::TABLE . ' (migration, batch) VALUES (:migration, :batch)'
                );
                $statement->execute(['migration' => $migrationName, 'batch' => $batch]);
                $this->pdo->commit();
                $executed[] = $migrationName;
            } catch (\Throwable $exception) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                throw $exception;
            }
        }

        return $executed;
    }

    private function appliedNames(): array
    {
        $rows = $this->pdo->query('SELECT migration FROM ' . self::TABLE . ' ORDER BY id')?->fetchAll() ?: [];
        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['migration']] = true;
        }
        return $result;
    }

    private function nextBatch(): int
    {
        $value = $this->pdo->query('SELECT COALESCE(MAX(batch), 0) + 1 FROM ' . self::TABLE)?->fetchColumn();
        return max(1, (int) $value);
    }
}
