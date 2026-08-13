<?php

declare(strict_types=1);

namespace Ispluka\Controllers\Auth;

use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Auth\Session;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
use Ispluka\Core\Security\Csrf;

final class LoginController
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly Session $session,
        private readonly Csrf $csrf,
    ) {
    }

    public function show(Request $request): Response
    {
        if ($this->auth->check()) {
            return Response::redirect('/');
        }

        $token = $this->csrf->token();
        $html = '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ISPLUKA Login</title></head><body><main><h1>ISPLUKA</h1><form method="post" action="/login"><input type="hidden" name="_csrf" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '"><label>Username or email <input name="login" type="text" autocomplete="username" required></label><label>Password <input name="password" type="password" autocomplete="current-password" required></label><button type="submit">Sign in</button></form></main></body></html>';

        return Response::text($html);
    }

    public function login(Request $request): Response
    {
        if (!$this->csrf->validate((string) $request->input('_csrf', ''))) {
            return Response::text('Invalid CSRF token.', 419);
        }

        $login = (string) $request->input('login', '');
        $password = (string) $request->input('password', '');

        if (!$this->auth->attempt($login, $password)) {
            return Response::text('Invalid credentials or account temporarily locked.', 422);
        }

        return Response::redirect('/');
    }

    public function logout(Request $request): Response
    {
        if (!$this->csrf->validate((string) $request->input('_csrf', ''))) {
            return Response::text('Invalid CSRF token.', 419);
        }

        $this->auth->logout();
        return Response::redirect('/login');
    }
}
