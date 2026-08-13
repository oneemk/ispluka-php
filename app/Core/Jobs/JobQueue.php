<?php

declare(strict_types=1);

namespace Ispluka\Core\Jobs;

use Ispluka\Core\Database\Database;
use PDO;

final class JobQueue
{
    public function __construct(private readonly Database $database) {}

    public function enqueue(int $tenantId, string $type, array $payload, ?string $availableAt = null): int
    {
        $stmt = $this->database->pdo()->prepare('INSERT INTO jobs (tenant_id, type, payload, status, available_at) VALUES (:tenant_id, :type, CAST(:payload AS jsonb), \'pending\', COALESCE(:available_at::timestamptz, CURRENT_TIMESTAMP)) RETURNING id');
        $stmt->execute([':tenant_id'=>$tenantId, ':type'=>$type, ':payload'=>json_encode($payload, JSON_THROW_ON_ERROR), ':available_at'=>$availableAt]);
        return (int)$stmt->fetchColumn();
    }

    public function claim(int $limit = 20): array
    {
        $pdo = $this->database->pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT id, tenant_id, type, payload, attempts FROM jobs WHERE status = 'pending' AND available_at <= CURRENT_TIMESTAMP ORDER BY id FOR UPDATE SKIP LOCKED LIMIT :limit");
            $stmt->bindValue(':limit', min(max($limit, 1), 100), PDO::PARAM_INT);
            $stmt->execute();
            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($jobs) {
                $ids = array_map('intval', array_column($jobs, 'id'));
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $update = $pdo->prepare("UPDATE jobs SET status = 'processing', attempts = attempts + 1, locked_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders)");
                $update->execute($ids);
            }
            $pdo->commit();
            return $jobs;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
