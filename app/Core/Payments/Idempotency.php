<?php

declare(strict_types=1);

namespace Ispluka\Core\Payments;

use Ispluka\Core\Database\Database;
use RuntimeException;

final class Idempotency
{
    public function __construct(private readonly Database $database) {}

    public function claim(int $tenantId, string $key, string $operation): bool
    {
        $key = trim($key);
        if ($key === '' || strlen($key) > 128) throw new RuntimeException('Invalid idempotency key.');
        $stmt = $this->database->pdo()->prepare(
            'INSERT INTO payment_idempotency (tenant_id, idempotency_key, operation) VALUES (:tenant_id, :key, :operation) ON CONFLICT (tenant_id, idempotency_key, operation) DO NOTHING'
        );
        $stmt->execute([':tenant_id'=>$tenantId, ':key'=>$key, ':operation'=>$operation]);
        return $stmt->rowCount() === 1;
    }
}
