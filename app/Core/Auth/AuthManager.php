<?php

declare(strict_types=1);

namespace Ispluka\Core\Auth;

use Ispluka\Core\Database\Database;
use RuntimeException;

final class AuthManager
{
    private const SESSION_KEY = 'auth.user_id';
    private const SESSION_TENANT_KEY = 'auth.tenant_id';

    public function __construct(
        private readonly Database $database,
        private readonly Session $session,
    ) {
    }

    public function attempt(string $login, string $password): bool
    {
        $login = trim($login);
        if ($login === '' || $password === '') {
            return false;
        }

        $statement = $this->database->pdo()->prepare(
            'SELECT id, tenant_id, password_hash, status, failed_login_attempts, locked_until
             FROM users
             WHERE email = :login OR username = :login
             LIMIT 1'
        );
        $statement->execute(['login' => $login]);
        $user = $statement->fetch();

        if (!is_array($user) || ($user['status'] ?? '') !== 'active') {
            return false;
        }

        if ($this->isLocked($user['locked_until'] ?? null)) {
            return false;
        }

        if (!Password::verify($password, (string) $user['password_hash'])) {
            $this->recordFailedAttempt((int) $user['id']);
            return false;
        }

        $this->clearFailedAttempts((int) $user['id']);
        $this->session->regenerate();
        $this->session->put(self::SESSION_KEY, (int) $user['id']);
        $this->session->put(self::SESSION_TENANT_KEY, $user['tenant_id'] !== null ? (int) $user['tenant_id'] : null);

        return true;
    }

    public function check(): bool
    {
        return $this->userId() !== null;
    }

    public function userId(): ?int
    {
        $value = $this->session->get(self::SESSION_KEY);
        return is_int($value) || (is_string($value) && ctype_digit($value)) ? (int) $value : null;
    }

    public function tenantId(): ?int
    {
        $value = $this->session->get(self::SESSION_TENANT_KEY);
        return is_int($value) || (is_string($value) && ctype_digit($value)) ? (int) $value : null;
    }

    public function logout(): void
    {
        $this->session->invalidate();
    }

    private function isLocked(mixed $lockedUntil): bool
    {
        return is_string($lockedUntil) && $lockedUntil !== '' && strtotime($lockedUntil) !== false && strtotime($lockedUntil) > time();
    }

    private function recordFailedAttempt(int $userId): void
    {
        $statement = $this->database->pdo()->prepare(
            "UPDATE users
             SET failed_login_attempts = failed_login_attempts + 1,
                 locked_until = CASE
                     WHEN failed_login_attempts + 1 >= :threshold THEN CURRENT_TIMESTAMP + INTERVAL '15 minutes'
                     ELSE locked_until
                 END
             WHERE id = :id"
        );
        $statement->execute(['threshold' => 5, 'id' => $userId]);
    }

    private function clearFailedAttempts(int $userId): void
    {
        $statement = $this->database->pdo()->prepare(
            'UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $statement->execute(['id' => $userId]);
    }
}
