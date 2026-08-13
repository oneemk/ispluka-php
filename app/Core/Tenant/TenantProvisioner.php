<?php

declare(strict_types=1);

namespace Ispluka\Core\Tenant;

use Ispluka\Core\Auth\Password;
use Ispluka\Core\Auth\RoleManager;
use Ispluka\Core\Database\Database;
use RuntimeException;

final class TenantProvisioner
{
    public function __construct(
        private readonly Database $database,
        private readonly RoleManager $roles,
    ) {
    }

    /** @return array{tenant_id:int,user_id:int} */
    public function createTenantWithAdmin(
        string $name,
        string $code,
        string $adminName,
        string $adminEmail,
        string $adminPassword,
    ): array {
        $name = trim($name);
        $code = strtolower(trim($code));
        $adminName = trim($adminName);
        $adminEmail = strtolower(trim($adminEmail));

        if ($name === '' || $code === '' || $adminName === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid tenant or administrator data.');
        }
        if (strlen($adminPassword) < 12) {
            throw new RuntimeException('Administrator password must contain at least 12 characters.');
        }

        return $this->database->transaction()->run(function () use ($name, $code, $adminName, $adminEmail, $adminPassword): array {
            $pdo = $this->database->pdo();
            $tenant = $pdo->prepare(
                "INSERT INTO tenants (name, code, timezone, currency)
                 VALUES (:name, :code, 'Asia/Dhaka', 'BDT')
                 RETURNING id"
            );
            $tenant->execute(['name' => $name, 'code' => $code]);
            $tenantId = (int) $tenant->fetchColumn();
            if ($tenantId <= 0) {
                throw new RuntimeException('Tenant creation failed.');
            }

            $this->roles->provisionTenantRoles($tenantId);

            $user = $pdo->prepare(
                'INSERT INTO users (tenant_id, name, email, password_hash, status, password_changed_at)
                 VALUES (:tenant_id, :name, :email, :password_hash, \'active\', CURRENT_TIMESTAMP)
                 RETURNING id'
            );
            $user->execute([
                'tenant_id' => $tenantId,
                'name' => $adminName,
                'email' => $adminEmail,
                'password_hash' => Password::hash($adminPassword),
            ]);
            $userId = (int) $user->fetchColumn();
            if ($userId <= 0) {
                throw new RuntimeException('Administrator creation failed.');
            }

            $this->roles->assign($userId, 'admin', $tenantId);

            return ['tenant_id' => $tenantId, 'user_id' => $userId];
        });
    }
}
