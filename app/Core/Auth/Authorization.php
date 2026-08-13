<?php

declare(strict_types=1);

namespace Ispluka\Core\Auth;

use Ispluka\Core\Database\Database;

final class Authorization
{
    public function __construct(
        private readonly Database $database,
        private readonly AuthManager $auth,
    ) {
    }

    public function hasRole(string $roleCode): bool
    {
        $userId = $this->auth->userId();
        if ($userId === null) {
            return false;
        }

        $statement = $this->database->pdo()->prepare(
            "SELECT 1
             FROM user_roles ur
             INNER JOIN roles r ON r.id = ur.role_id
             INNER JOIN users u ON u.id = ur.user_id
             WHERE ur.user_id = :user_id
               AND r.code = :role_code
               AND (r.tenant_id IS NULL OR r.tenant_id = u.tenant_id)
             LIMIT 1"
        );
        $statement->execute([
            'user_id' => $userId,
            'role_code' => $roleCode,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function can(string $permission): bool
    {
        $userId = $this->auth->userId();
        if ($userId === null) {
            return false;
        }

        $statement = $this->database->pdo()->prepare(
            "SELECT 1
             FROM user_roles ur
             INNER JOIN roles r ON r.id = ur.role_id
             INNER JOIN role_permissions rp ON rp.role_id = r.id
             INNER JOIN permissions p ON p.id = rp.permission_id
             INNER JOIN users u ON u.id = ur.user_id
             WHERE ur.user_id = :user_id
               AND p.code = :permission
               AND (r.tenant_id IS NULL OR r.tenant_id = u.tenant_id)
             LIMIT 1"
        );
        $statement->execute([
            'user_id' => $userId,
            'permission' => $permission,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function require(string $permission): void
    {
        if (!$this->can($permission)) {
            throw new \RuntimeException('Forbidden.', 403);
        }
    }
}
