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
    public function __construct(private readonly AuthManager $auth, private readonly Session $session, private readonly Csrf $csrf) {}

    public function show(Request $request): Response
    {
        if ($this->auth->check()) return Response::redirect('/');
        $token = htmlspecialchars($this->csrf->token(), ENT_QUOTES, 'UTF-8');
        $html = '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#2563eb"><link rel="manifest" href="/manifest.json"><link rel="stylesheet" href="/assets/css/app.css"><title>ISPLUKA Login</title></head><body class="login-page"><main class="login-card"><h1>ISPLUKA</h1><p class="muted">Select your account type</p><form class="stack" method="post" action="/login"><input type="hidden" name="_csrf" value="'.$token.'"><div class="form-grid"><label><input type="radio" name="role" value="master_admin"> Master Admin</label><label><input type="radio" name="role" value="admin" checked> ISP Admin</label><label><input type="radio" name="role" value="reseller"> Reseller</label><label><input type="radio" name="role" value="employee"> Employee</label></div><div><label for="login">Username or email</label><input id="login" name="login" type="text" autocomplete="username" autocapitalize="none" required></div><div><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div><button type="submit">Sign in</button></form></main><script src="/assets/js/app.js" defer></script></body></html>';
        return Response::text($html);
    }

    public function login(Request $request): Response
    {
        if (!$this->csrf->validate((string) $request->input('_csrf', ''))) return Response::text('Invalid CSRF token.', 419);
        $role = trim((string) $request->input('role', 'admin'));
        if (!in_array($role, ['master_admin', 'admin', 'reseller', 'employee'], true)) {
            return Response::text('Invalid account type.', 422);
        }
        if (!$this->auth->attempt((string) $request->input('login', ''), (string) $request->input('password', ''), $role)) return Response::text('Invalid credentials, account type, or account temporarily locked.', 422);
        return Response::redirect('/');
    }

    public function logout(Request $request): Response
    {
        if (!$this->csrf->validate((string) $request->input('_csrf', ''))) return Response::text('Invalid CSRF token.', 419);
        $this->auth->logout();
        return Response::redirect('/login');
    }
}
