<?php

declare(strict_types=1);

namespace Ispluka\Core\Auth;

use RuntimeException;

final class Session
{
    private const COOKIE_NAME = 'ISPLUKA_SESSION';
    private bool $started = false;

    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }

        session_name(self::COOKIE_NAME);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        $configured = session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $this->isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        if (!$configured || !session_start()) {
            throw new RuntimeException('Unable to start secure session.');
        }

        $this->started = true;
    }

    public function regenerate(): void
    {
        $this->start();
        if (!session_regenerate_id(true)) {
            throw new RuntimeException('Unable to regenerate secure session.');
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function forget(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function invalidate(): void
    {
        $this->start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => (bool) ($params['secure'] ?? false),
                'httponly' => (bool) ($params['httponly'] ?? true),
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
        $this->started = false;
    }

    private function isHttps(): bool
    {
        if (isset($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        if (is_string($forwardedProto)) {
            return strtolower(trim(explode(',', $forwardedProto)[0])) === 'https';
        }

        return false;
    }
}
