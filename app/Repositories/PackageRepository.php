<?php

declare(strict_types=1);

namespace Ispluka\Repositories;

use Ispluka\Core\Database\Database;
use PDO;

final class PackageRepository
{
    public function __construct(private readonly Database $database) {}

    public function listByTenant(int $tenantId, int $limit, int $offset): array
    {
        $stmt = $this->database->pdo()->prepare(
            'SELECT id, name, code, description, service_type, download_kbps, upload_kbps, price, billing_period, validity_days, status, settings, created_at, updated_at FROM packages WHERE tenant_id = :tenant_id ORDER BY id DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':tenant_id', $tenantId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(int $tenantId, array $data): int
    {
        $stmt = $this->database->pdo()->prepare(
            'INSERT INTO packages (tenant_id, name, code, description, service_type, download_kbps, upload_kbps, price, billing_period, validity_days, status, settings) VALUES (:tenant_id, :name, :code, :description, :service_type, :download_kbps, :upload_kbps, :price, :billing_period, :validity_days, :status, CAST(:settings AS jsonb)) RETURNING id'
        );
        $stmt->execute([
            ':tenant_id' => $tenantId, ':name' => $data['name'], ':code' => $data['code'], ':description' => $data['description'],
            ':service_type' => $data['service_type'], ':download_kbps' => $data['download_kbps'], ':upload_kbps' => $data['upload_kbps'],
            ':price' => $data['price'], ':billing_period' => $data['billing_period'], ':validity_days' => $data['validity_days'],
            ':status' => $data['status'], ':settings' => json_encode($data['settings'], JSON_THROW_ON_ERROR),
        ]);
        return (int) $stmt->fetchColumn();
    }
}
