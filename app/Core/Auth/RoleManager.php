<?php

declare(strict_types=1);

namespace Ispluka\Core\Auth;

use Ispluka\Core\Database\Database;
use PDO;
use RuntimeException;

final class RoleManager
{
    private const TENANT_ROLES = [
        'admin' => 'Admin',
        'reseller' => 'Reseller',
        'employee' => 'Employee',
        'customer' => 'Customer',
    ];

    public function __construct(private readonly Database $database)
    {
    }

    public function provisionTenantRoles(int $tenantId): void
    {
        $statement = $this->database->pdo()->prepare(
            'INSERT INTO roles (tenant_id, name, code, description)
             VALUES (:tenant_id, :name, :code, :description)
             ON CONFLICT (tenant_id, code) DO NOTHING'
        );

        foreach (self::TENANT_ROLES as $code => $name) {
            $statement->execute([
                'tenant_id' => $tenantId,
                'name' => $name,
                'code' => $code,
                'description' => $name . ' tenant role',
            ]);
        }
    }

    public function assign(int $userId, string $roleCode, ?int $tenantId): void
    {
        $pdo = $this->database->pdo();
        $statement = $pdo->prepare(
            'SELECT id, tenant_id FROM roles
             WHERE code = ?
               AND (tenant_id IS NULL OR tenant_id = ?)
             ORDER BY CASE WHEN tenant_id = ? THEN 0 ELSE 1 END
             LIMIT 1'
        );
        $statement->execute([$roleCode, $tenantId, $tenantId]);
        $role = $statement->fetch(PDO::FETCH_ASSOC);

        if (!is_array($role)) {
            throw new RuntimeException('Role not found.');
        }

        if ($role['tenant_id'] === null && $roleCode !== 'master_admin') {
            throw new RuntimeException('Global role assignment is not allowed for this role.');
        }

        $pdo->prepare(
            'INSERT INTO user_roles (user_id, role_id)
             VALUES (:user_id, :role_id)
             ON CONFLICT DO NOTHING'
        )->execute([
            'user_id' => $userId,
            'role_id' => (int) $role['id'],
        ]);
    }
}
