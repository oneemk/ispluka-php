<?php

declare(strict_types=1);

namespace Ispluka\Core\Database;

use PDO;

final class Database
{
    private readonly Connection $connection;

    public function __construct(array $config)
    {
        $this->connection = new Connection($config);
    }

    public function pdo(): PDO
    {
        return $this->connection->pdo();
    }

    public function transaction(): Transaction
    {
        return new Transaction($this->pdo());
    }
}
