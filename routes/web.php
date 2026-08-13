<?php

declare(strict_types=1);

use Ispluka\Controllers\Auth\LoginController;
use Ispluka\Controllers\CustomerController;
use Ispluka\Controllers\CustomerServiceController;
use Ispluka\Controllers\MikrotikEnforcementAuditController;
use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Http\Response;
use Ispluka\Core\Routing\Router;
use Ispluka\Core\Security\Csrf;
use Ispluka\Middleware\Authorize;
use Ispluka\Middleware\RequireAuthentication;

return static function (Router $router, LoginController $loginController, AuthManager $auth, Csrf $csrf, Authorize $authorize, CustomerController $customers, CustomerServiceController $customerServices, callable $csrfMiddleware, MikrotikEnforcementAuditController $mikrotikAudit): void {
    $requireAuth = new RequireAuthentication($auth);

    $router->get('/', static function () use ($auth, $csrf): Response {
        $token = htmlspecialchars($csrf->token(), ENT_QUOTES, 'UTF-8');
        $userId = (int) $auth->userId();
        $html = '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#2563eb"><link rel="manifest" href="/manifest.json"><link rel="stylesheet" href="/assets/css/app.css"><title>ISPLUKA Dashboard</title></head><body><div class="app-shell"><header class="app-header"><div class="container header-inner"><button class="menu-toggle" type="button" data-menu-toggle aria-label="Open navigation" aria-expanded="false">☰</button><a class="brand" href="/">ISPLUKA</a><form method="post" action="/logout"><input type="hidden" name="_csrf" value="'.$token.'"><button class="btn-danger" type="submit">Sign out</button></form></div></header><aside class="sidebar" data-sidebar><nav class="nav"><a class="active" href="/">Dashboard</a><a href="/api/customers">Customers</a><a href="/api/customer-services">Services</a><a href="/networking/mikrotik/enforcement-audit">MikroTik Audit</a><a href="/admin">Admin</a></nav></aside><main class="main main-with-sidebar"><div class="container"><div class="page-title"><div><h1>Dashboard</h1><div class="muted">Mobile-ready ISP management</div></div></div><div class="grid grid-4"><section class="card"><div class="muted">User</div><div class="stat-value">#'.$userId.'</div></section><section class="card"><div class="muted">Customers</div><div class="stat-value">—</div></section><section class="card"><div class="muted">Active Services</div><div class="stat-value">—</div></section><section class="card"><div class="muted">Today Payments</div><div class="stat-value">—</div></section></div><section class="card" style="margin-top:1rem"><div class="page-title"><h2>Quick actions</h2></div><div class="actions"><a class="btn" href="/api/customers">Customers</a><a class="btn btn-secondary" href="/api/customer-services">Services</a><a class="btn btn-secondary" href="/networking/mikrotik/enforcement-audit">MikroTik Audit</a></div></section></div></main><nav class="bottom-nav"><a class="active" href="/">⌂<br>Home</a><a href="/api/customers">👥<br>Customers</a><a href="/api/customer-services">⚡<br>Services</a><a href="/networking/mikrotik/enforcement-audit">📡<br>MikroTik</a></nav></div><script src="/assets/js/app.js" defer></script></body></html>';
        return Response::text($html);
    }, [$requireAuth]);

    $router->get('/admin', static fn (): Response => Response::text('Admin area'), [$requireAuth, $authorize->permission('users.view')]);
    $customerView = $authorize->permission('customers.view'); $customerCreate = $authorize->permission('customers.create'); $customerUpdate = $authorize->permission('customers.update'); $customerDelete = $authorize->permission('customers.delete'); $serviceView = $authorize->permission('services.view'); $serviceManage = $authorize->permission('services.manage');
    $router->get('/api/customers', [$customers, 'index'], [$requireAuth, $customerView]); $router->get('/api/customer', [$customers, 'show'], [$requireAuth, $customerView]); $router->post('/api/customers', [$customers, 'store'], [$requireAuth, $customerCreate, $csrfMiddleware]); $router->post('/api/customer/update', [$customers, 'update'], [$requireAuth, $customerUpdate, $csrfMiddleware]); $router->post('/api/customer/delete', [$customers, 'destroy'], [$requireAuth, $customerDelete, $csrfMiddleware]);
    $router->get('/api/customer-services', [$customerServices, 'index'], [$requireAuth, $serviceView]); $router->post('/api/customer-services', [$customerServices, 'store'], [$requireAuth, $serviceManage, $csrfMiddleware]); $router->post('/api/customer-service/status', [$customerServices, 'status'], [$requireAuth, $serviceManage, $csrfMiddleware]);
    $auditView = $authorize->permission('routers.view');
    $router->get('/networking/mikrotik/enforcement-audit', [$mikrotikAudit, 'page'], [$requireAuth, $auditView]);
    $router->get('/api/mikrotik/pppoe/enforcement-audit', [$mikrotikAudit, 'audit'], [$requireAuth, $auditView]);
    $router->get('/api/mikrotik/pppoe/enforcement-audit/summary', [$mikrotikAudit, 'summary'], [$requireAuth, $auditView]);
    $router->get('/login', [$loginController, 'show']); $router->post('/login', [$loginController, 'login']); $router->post('/logout', [$loginController, 'logout'], [$requireAuth]);
};
