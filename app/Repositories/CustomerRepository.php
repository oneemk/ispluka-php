<?php

declare(strict_types=1);

namespace Ispluka\Repositories;

use Ispluka\Core\Database\Database;
use PDO;
use RuntimeException;

final class CustomerRepository
{
    public function __construct(private readonly Database $database)
    {
    }

    public function paginate(int $tenantId, int $page = 1, int $perPage = 25, ?string $search = null): array
    {
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = 'tenant_id = :tenant_id AND deleted_at IS NULL';
        $params = ['tenant_id' => $tenantId];

        if ($search !== null && trim($search) !== '') {
            $where .= ' AND (customer_code ILIKE :search OR name ILIKE :search OR phone ILIKE :search OR email ILIKE :search)';
            $params['search'] = '%' . trim($search) . '%';
        }

        $count = $this->database->pdo()->prepare("SELECT COUNT(*) FROM customers WHERE {$where}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $statement = $this->database->pdo()->prepare(
            "SELECT id, customer_code, name, phone, email, address, area, status, billing_day, credit_limit, balance, created_at, updated_at
             FROM customers WHERE {$where} ORDER BY id DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'items' => $statement->fetchAll(PDO::FETCH_ASSOC),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'pages' => $total === 0 ? 0 : (int) ceil($total / $perPage),
            ],
        ];
    }

    public function find(int $tenantId, int $id): ?array
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT id, customer_code, name, phone, email, nid, address, area, latitude, longitude, status, billing_day, credit_limit, balance, metadata, created_at, updated_at
             FROM customers WHERE tenant_id = :tenant_id AND id = :id AND deleted_at IS NULL LIMIT 1'
        );
        $statement->execute(['tenant_id' => $tenantId, 'id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function create(int $tenantId, array $data): int
    {
        $statement = $this->database->pdo()->prepare(
            'INSERT INTO customers (tenant_id, reseller_id, customer_code, name, phone, email, nid, address, area, latitude, longitude, status, billing_day, credit_limit, metadata)
             VALUES (:tenant_id, :reseller_id, :customer_code, :name, :phone, :email, :nid, :address, :area, :latitude, :longitude, :status, :billing_day, :credit_limit, :metadata)
             RETURNING id'
        );
        $statement->execute([
            'tenant_id' => $tenantId,
            'reseller_id' => $data['reseller_id'] ?? null,
            'customer_code' => $data['customer_code'],
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'nid' => $data['nid'] ?? null,
            'address' => $data['address'] ?? null,
            'area' => $data['area'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'status' => $data['status'] ?? 'active',
            'billing_day' => $data['billing_day'] ?? 1,
            'credit_limit' => $data['credit_limit'] ?? 0,
            'metadata' => json_encode($data['metadata'] ?? [], JSON_THROW_ON_ERROR),
        ]);
        return (int) $statement->fetchColumn();
    }

    public function update(int $tenantId, int $id, array $data): bool
    {
        $allowed = ['name','phone','email','nid','address','area','latitude','longitude','status','billing_day','credit_limit','reseller_id','metadata'];
        $sets = [];
        $params = ['tenant_id' => $tenantId, 'id' => $id];
        foreach ($allowed as $field) {
            if (!array_key_exists($field, $data)) continue;
            $sets[] = $field . ' = :' . $field;
            $params[$field] = $field === 'metadata' ? json_encode($data[$field], JSON_THROW_ON_ERROR) : $data[$field];
        }
        if ($sets === []) return false;
        $sets[] = 'updated_at = CURRENT_TIMESTAMP';
        $statement = $this->database->pdo()->prepare('UPDATE customers SET ' . implode(', ', $sets) . ' WHERE tenant_id = :tenant_id AND id = :id AND deleted_at IS NULL');
        $statement->execute($params);
        return $statement->rowCount() === 1;
    }

    public function softDelete(int $tenantId, int $id): bool
    {
        $statement = $this->database->pdo()->prepare('UPDATE customers SET deleted_at = CURRENT_TIMESTAMP, status = \'closed\', updated_at = CURRENT_TIMESTAMP WHERE tenant_id = :tenant_id AND id = :id AND deleted_at IS NULL');
        $statement->execute(['tenant_id' => $tenantId, 'id' => $id]);
        return $statement->rowCount() === 1;
    }
}
