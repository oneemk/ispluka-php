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
    private const NO_CACHE = [
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
        'Expires' => '0',
        'Vary' => 'Cookie',
    ];

    public function __construct(private readonly AuthManager $auth, private readonly Session $session, private readonly Csrf $csrf) {}

    public function show(Request $request): Response
    {
        if ($this->auth->check()) return Response::redirect('/');
        $token = htmlspecialchars($this->csrf->token(), ENT_QUOTES, 'UTF-8');
        $ok = isset($_GET['signup']) && $_GET['signup'] === 'success' ? '<div class="card" style="border-color:#86efac;background:#f0fdf4;color:#166534">Account created. Sign in to start your trial.</div>' : '';
        $html = '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#2563eb"><link rel="manifest" href="/manifest.json"><link rel="stylesheet" href="/assets/css/app.css"><title>ISPLUKA Login</title></head><body class="login-page"><main class="login-card">'.$ok.'<h1>ISPLUKA</h1><p class="muted">Sign in once. ISPLUKA automatically opens the dashboard for your account role.</p><form class="stack" method="post" action="/login"><input type="hidden" name="_csrf" value="'.$token.'"><div><label for="login">Username or email</label><input id="login" name="login" type="text" autocomplete="username" autocapitalize="none" required></div><div><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div><button type="submit">Sign in</button></form><p class="muted">New ISP? <a href="/signup">Create Admin account · 30-day free trial</a></p></main></body></html>';
        return Response::text($html, 200, self::NO_CACHE);
    }

    public function login(Request $request): Response
    {
        if (!$this->csrf->validate((string) $request->input('_csrf', ''))) return Response::text('Invalid CSRF token.', 419, self::NO_CACHE);
        if (!$this->auth->attempt((string) $request->input('login', ''), (string) $request->input('password', ''))) return Response::text('Invalid credentials or account temporarily locked.', 422, self::NO_CACHE);
        return Response::redirect('/');
    }

    public function logout(Request $request): Response
    {
        if (!$this->csrf->validate((string) $request->input('_csrf', ''))) return Response::text('Invalid CSRF token.', 419, self::NO_CACHE);
        $this->auth->logout();
        return Response::redirect('/login');
    }
}
