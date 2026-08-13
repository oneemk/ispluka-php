<?php

declare(strict_types=1);

namespace Ispluka\Core\Security;

use Ispluka\Core\Auth\Session;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public function __construct(private readonly Session $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);
        if (is_string($token) && strlen($token) >= 32) {
            return $token;
        }

        $token = bin2hex(random_bytes(32));
        $this->session->put(self::SESSION_KEY, $token);
        return $token;
    }

    public function validate(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($this->token(), $token);
    }
}
