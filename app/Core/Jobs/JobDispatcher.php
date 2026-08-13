<?php

declare(strict_types=1);

namespace Ispluka\Core\Jobs;

use Ispluka\Core\Database\Database;
use RuntimeException;

final class JobDispatcher
{
    /** @var array<string, callable(array): void> */
    private array $handlers = [];

    public function __construct(private readonly Database $database) {}

    public function register(string $type, callable $handler): void
    {
        $this->handlers[$type] = $handler;
    }

    public function dispatch(string $type, array $payload): void
    {
        if (!isset($this->handlers[$type])) throw new RuntimeException('No handler registered for job type.');
        ($this->handlers[$type])($payload);
    }

    public function complete(int $id): void
    {
        $stmt = $this->database->pdo()->prepare("UPDATE jobs SET status='completed', completed_at=CURRENT_TIMESTAMP, updated_at=CURRENT_TIMESTAMP WHERE id=:id AND status='processing'");
        $stmt->execute([':id'=>$id]);
    }

    public function fail(int $id, string $error): void
    {
        $stmt = $this->database->pdo()->prepare("UPDATE jobs SET status=CASE WHEN attempts >= max_attempts THEN 'failed' ELSE 'pending' END, last_error=:error, available_at=CURRENT_TIMESTAMP + INTERVAL '5 minutes', updated_at=CURRENT_TIMESTAMP WHERE id=:id AND status='processing'");
        $stmt->execute([':id'=>$id, ':error'=>mb_substr($error, 0, 1000)]);
    }
}
