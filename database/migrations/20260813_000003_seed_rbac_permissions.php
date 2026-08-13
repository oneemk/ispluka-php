<?php

declare(strict_types=1);

use Ispluka\Database\Migrations\MigrationInterface;
use PDO;

return new class implements MigrationInterface
{
    public function up(PDO $pdo): void
    {
        $permissions = [
            ['platform.manage', 'Manage SaaS platform'],
            ['tenant.manage', 'Manage ISP tenant'],
            ['users.view', 'View users'],
            ['users.create', 'Create users'],
            ['users.update', 'Update users'],
            ['users.delete', 'Delete users'],
            ['roles.view', 'View roles'],
            ['roles.manage', 'Manage roles and permissions'],
            ['customers.view', 'View customers'],
            ['customers.create', 'Create customers'],
            ['customers.update', 'Update customers'],
            ['customers.delete', 'Delete customers'],
            ['packages.view', 'View packages'],
            ['packages.manage', 'Manage packages'],
            ['billing.view', 'View billing'],
            ['billing.manage', 'Manage billing'],
            ['payments.view', 'View payments'],
            ['payments.manage', 'Manage payments'],
            ['routers.view', 'View routers'],
            ['routers.manage', 'Manage routers'],
            ['services.view', 'View customer services'],
            ['services.manage', 'Manage customer services'],
            ['reports.view', 'View reports'],
            ['audit.view', 'View audit logs'],
            ['settings.manage', 'Manage tenant settings'],
            ['api.access', 'Access REST API'],
        ];

        $permissionStatement = $pdo->prepare(
            'INSERT INTO permissions (code, description)
             VALUES (:code, :description)
             ON CONFLICT (code) DO UPDATE SET description = EXCLUDED.description'
        );

        foreach ($permissions as [$code, $description]) {
            $permissionStatement->execute([
                'code' => $code,
                'description' => $description,
            ]);
        }

        $roleStatement = $pdo->prepare(
            "INSERT INTO roles (tenant_id, name, code, description)
             VALUES (NULL, 'Master Admin', 'master_admin', 'Platform-wide administrator')
             ON CONFLICT DO NOTHING"
        );
        $roleStatement->execute();

        $masterRole = $pdo->query(
            "SELECT id FROM roles WHERE tenant_id IS NULL AND code = 'master_admin' LIMIT 1"
        )->fetchColumn();

        if ($masterRole === false) {
            throw new RuntimeException('Unable to create master_admin role.');
        }

        $permissionIds = $pdo->query('SELECT id FROM permissions')->fetchAll(PDO::FETCH_COLUMN);
        $assignment = $pdo->prepare(
            'INSERT INTO role_permissions (role_id, permission_id)
             VALUES (:role_id, :permission_id)
             ON CONFLICT DO NOTHING'
        );

        foreach ($permissionIds as $permissionId) {
            $assignment->execute([
                'role_id' => (int) $masterRole,
                'permission_id' => (int) $permissionId,
            ]);
        }
    }

    public function down(PDO $pdo): void
    {
        $roleId = $pdo->query(
            "SELECT id FROM roles WHERE tenant_id IS NULL AND code = 'master_admin' LIMIT 1"
        )->fetchColumn();

        if ($roleId !== false) {
            $pdo->prepare('DELETE FROM role_permissions WHERE role_id = :role_id')->execute(['role_id' => (int) $roleId]);
            $pdo->prepare('DELETE FROM roles WHERE id = :id')->execute(['id' => (int) $roleId]);
        }

        $pdo->exec('DELETE FROM permissions');
    }
};
