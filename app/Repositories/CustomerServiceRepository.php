<?php

declare(strict_types=1);

namespace Ispluka\Repositories;

use Ispluka\Core\Database\Database;

final class CustomerServiceRepository
{
    public function __construct(private readonly Database $database)
    {
    }

    public function list(int $tenantId, int $customerId): array
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT cs.id, cs.customer_id, cs.package_id, cs.router_id, cs.service_type, cs.username, cs.mac_address, cs.ip_address::text AS ip_address, cs.start_date, cs.next_billing_date, cs.status, cs.auto_suspend, cs.settings, p.name AS package_name, r.name AS router_name
             FROM customer_services cs
             LEFT JOIN packages p ON p.id = cs.package_id AND p.tenant_id = cs.tenant_id
             LEFT JOIN routers r ON r.id = cs.router_id AND r.tenant_id = cs.tenant_id
             WHERE cs.tenant_id = :tenant_id AND cs.customer_id = :customer_id
             ORDER BY cs.id DESC'
        );
        $statement->execute(['tenant_id' => $tenantId, 'customer_id' => $customerId]);
        return $statement->fetchAll();
    }

    public function create(int $tenantId, int $customerId, array $data): int
    {
        $statement = $this->database->pdo()->prepare(
            'INSERT INTO customer_services (tenant_id, customer_id, package_id, router_id, service_type, username, encrypted_secret, mac_address, ip_address, start_date, next_billing_date, status, auto_suspend, settings)
             VALUES (:tenant_id, :customer_id, :package_id, :router_id, :service_type, :username, :encrypted_secret, :mac_address, :ip_address, :start_date, :next_billing_date, :status, :auto_suspend, :settings)
             RETURNING id'
        );
        $statement->execute([
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'package_id' => $data['package_id'] ?? null,
            'router_id' => $data['router_id'] ?? null,
            'service_type' => $data['service_type'],
            'username' => $data['username'] ?? null,
            'encrypted_secret' => $data['encrypted_secret'] ?? null,
            'mac_address' => $data['mac_address'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'start_date' => $data['start_date'],
            'next_billing_date' => $data['next_billing_date'] ?? null,
            'status' => $data['status'] ?? 'active',
            'auto_suspend' => $data['auto_suspend'] ?? true,
            'settings' => json_encode($data['settings'] ?? [], JSON_THROW_ON_ERROR),
        ]);
        return (int) $statement->fetchColumn();
    }

    public function updateStatus(int $tenantId, int $serviceId, string $status): bool
    {
        $statement = $this->database->pdo()->prepare(
            'UPDATE customer_services SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE tenant_id = :tenant_id AND id = :id'
        );
        $statement->execute(['status' => $status, 'tenant_id' => $tenantId, 'id' => $serviceId]);
        return $statement->rowCount() === 1;
    }
}
