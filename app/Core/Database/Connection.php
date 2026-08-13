<?php

declare(strict_types=1);

namespace Ispluka\Core\Database;

use PDO;
use PDOException;
use RuntimeException;

final class Connection
{
    private ?PDO $pdo = null;

    public function __construct(private readonly array $config)
    {
    }

    public function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $host = (string) ($this->config['host'] ?? '127.0.0.1');
        $port = (string) ($this->config['port'] ?? '5432');
        $database = (string) ($this->config['database'] ?? '');
        $username = (string) ($this->config['username'] ?? '');
        $password = (string) ($this->config['password'] ?? '');
        $sslMode = (string) ($this->config['sslmode'] ?? 'prefer');

        if ($database === '' || $username === '') {
            throw new RuntimeException('PostgreSQL database configuration is incomplete.');
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
            $host,
            $port,
            $database,
            $sslMode,
        );

        try {
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Unable to connect to PostgreSQL.', 0, $exception);
        }

        return $this->pdo;
    }

    public function disconnect(): void
    {
        $this->pdo = null;
    }
}
