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

    private const DEFAULT_ROLE_PERMISSIONS = [
        'admin' => ['tenant.manage', 'users.view', 'users.create', 'users.update', 'users.delete', 'roles.view', 'roles.manage', 'customers.view', 'customers.create', 'customers.update', 'customers.delete', 'packages.view', 'packages.manage', 'billing.view', 'billing.manage', 'payments.view', 'payments.manage', 'routers.view', 'routers.manage', 'services.view', 'services.manage', 'reports.view', 'audit.view', 'settings.manage', 'api.access'],
        'reseller' => ['customers.view', 'customers.create', 'customers.update', 'packages.view', 'billing.view', 'payments.view', 'services.view', 'services.manage', 'reports.view', 'api.access'],
        'employee' => ['customers.view', 'customers.create', 'customers.update', 'packages.view', 'billing.view', 'payments.view', 'services.view', 'services.manage', 'reports.view'],
        'customer' => ['customers.view', 'packages.view', 'billing.view', 'payments.view', 'services.view'],
    ];

    public function __construct(private readonly Database $database)
    {
    }

    public function provisionTenantRoles(int $tenantId): void
    {
        $pdo = $this->database->pdo();
        $statement = $pdo->prepare(
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

        $roleLookup = $pdo->prepare('SELECT id FROM roles WHERE tenant_id = :tenant_id AND code = :code LIMIT 1');
        $permissionLookup = $pdo->prepare('SELECT id FROM permissions WHERE code = :code LIMIT 1');
        $assignment = $pdo->prepare(
            'INSERT INTO role_permissions (role_id, permission_id)
             VALUES (:role_id, :permission_id)
             ON CONFLICT DO NOTHING'
        );

        foreach (self::DEFAULT_ROLE_PERMISSIONS as $roleCode => $permissions) {
            $roleLookup->execute(['tenant_id' => $tenantId, 'code' => $roleCode]);
            $roleId = $roleLookup->fetchColumn();
            if ($roleId === false) {
                throw new RuntimeException('Tenant role provisioning failed.');
            }

            foreach ($permissions as $permissionCode) {
                $permissionLookup->execute(['code' => $permissionCode]);
                $permissionId = $permissionLookup->fetchColumn();
                if ($permissionId === false) {
                    throw new RuntimeException('Permission not found: ' . $permissionCode);
                }
                $assignment->execute(['role_id' => (int) $roleId, 'permission_id' => (int) $permissionId]);
            }
        }
    }

    public function assign(int $userId, string $roleCode, ?int $tenantId): void
    {
        $pdo = $this->database->pdo();
        $userStatement = $pdo->prepare('SELECT tenant_id FROM users WHERE id = :user_id AND deleted_at IS NULL LIMIT 1');
        $userStatement->execute(['user_id' => $userId]);
        $userTenantId = $userStatement->fetchColumn();

        if ($userTenantId === false || ($userTenantId !== null && (int) $userTenantId !== $tenantId)) {
            throw new RuntimeException('User and tenant scope do not match.');
        }

        if ($roleCode === 'master_admin' && $tenantId !== null) {
            throw new RuntimeException('Master Admin must not belong to a tenant.');
        }

        if ($roleCode !== 'master_admin' && $tenantId === null) {
            throw new RuntimeException('Tenant roles require a tenant scope.');
        }

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
        )->execute(['user_id' => $userId, 'role_id' => (int) $role['id']]);
    }
}
