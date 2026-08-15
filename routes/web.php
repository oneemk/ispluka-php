<?php

declare(strict_types=1);

use Ispluka\Controllers\Auth\LoginController;
use Ispluka\Controllers\CustomerController;
use Ispluka\Controllers\CustomerServiceController;
use Ispluka\Controllers\MikrotikEnforcementAuditController;
use Ispluka\Controllers\MikrotikManualActionController;
use Ispluka\Controllers\TenantController;
use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Http\Response;
use Ispluka\Core\Routing\Router;
use Ispluka\Core\Security\Csrf;
use Ispluka\Middleware\Authorize;
use Ispluka\Middleware\RequireAuthentication;

return static function (Router $router, LoginController $loginController, AuthManager $auth, Csrf $csrf, Authorize $authorize, CustomerController $customers, CustomerServiceController $customerServices, callable $csrfMiddleware, MikrotikEnforcementAuditController $mikrotikAudit, MikrotikManualActionController $mikrotikManual, TenantController $tenants): void {
    $requireAuth = new RequireAuthentication($auth);
    $router->get('/', static function () use ($auth, $csrf, $authorize): Response {
        $token=htmlspecialchars($csrf->token(),ENT_QUOTES,'UTF-8');
        $userId=(int)$auth->userId();
        $master=$authorize->hasRole('master_admin');
        $tenantNav=$master ? '<a href="/admin/tenants">Tenants / Admins</a>' : '';
        $tenantAction=$master ? '<a class="btn" href="/admin/tenants">Create Tenant / Admin</a>' : '';
        $label=$master ? 'Master Admin' : 'Tenant Admin';
        return Response::text('<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#2563eb"><link rel="manifest" href="/manifest.json"><link rel="stylesheet" href="/assets/css/app.css"><title>ISPLUKA Dashboard</title></head><body><div class="app-shell"><header class="app-header"><div class="container header-inner"><button class="menu-toggle" type="button" data-menu-toggle aria-label="Open navigation" aria-expanded="false">☰</button><a class="brand" href="/">ISPLUKA</a><form method="post" action="/logout"><input type="hidden" name="_csrf" value="'.$token.'"><button class="btn-danger" type="submit">Sign out</button></form></div></header><aside class="sidebar" data-sidebar><nav class="nav"><a class="active" href="/">Dashboard</a>'.$tenantNav.'<a href="/api/customers">Customers</a><a href="/api/customer-services">Services</a><a href="/networking/customer">Customer Networking</a><a href="/networking/mikrotik/enforcement-audit">MikroTik Audit</a></nav></aside><main class="main main-with-sidebar"><div class="container"><h1>Dashboard</h1><p class="muted">'.$label.' · User #'.$userId.'</p><div class="actions">'.$tenantAction.'<a class="btn btn-secondary" href="/api/customers">Customers</a><a class="btn btn-secondary" href="/api/customer-services">Services</a><a class="btn btn-secondary" href="/networking/customer">Customer Networking</a><a class="btn btn-secondary" href="/networking/mikrotik/enforcement-audit">MikroTik Audit</a></div></div></main></div><script src="/assets/js/app.js" defer></script></body></html>');
    },[$requireAuth]);
    $masterOnly=$authorize->role('master_admin');
    $router->get('/admin',static fn():Response=>Response::redirect('/admin/tenants'),[$requireAuth,$masterOnly]);
    $router->get('/admin/tenants',[$tenants,'page'],[$requireAuth,$masterOnly]);
    $router->post('/admin/tenants',[$tenants,'store'],[$requireAuth,$masterOnly,$csrfMiddleware]);

    $customerView=$authorize->permission('customers.view');$customerCreate=$authorize->permission('customers.create');$customerUpdate=$authorize->permission('customers.update');$customerDelete=$authorize->permission('customers.delete');$serviceView=$authorize->permission('services.view');$serviceManage=$authorize->permission('services.manage');
    $router->get('/api/customers',[$customers,'index'],[$requireAuth,$customerView]);$router->get('/api/customer',[$customers,'show'],[$requireAuth,$customerView]);$router->post('/api/customers',[$customers,'store'],[$requireAuth,$customerCreate,$csrfMiddleware]);$router->post('/api/customer/update',[$customers,'update'],[$requireAuth,$customerUpdate,$csrfMiddleware]);$router->post('/api/customer/delete',[$customers,'destroy'],[$requireAuth,$customerDelete,$csrfMiddleware]);
    $router->get('/api/customer-services',[$customerServices,'index'],[$requireAuth,$serviceView]);$router->post('/api/customer-services',[$customerServices,'store'],[$requireAuth,$serviceManage,$csrfMiddleware]);$router->post('/api/customer-service/status',[$customerServices,'status'],[$requireAuth,$serviceManage,$csrfMiddleware]);
    $auditView=$authorize->permission('routers.view');$networkManage=$authorize->permission('routers.manage');
    $router->get('/networking/customer',static fn():Response=>Response::text('<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#2563eb"><link rel="stylesheet" href="/assets/css/app.css"><title>Customer Networking</title></head><body><main class="main"><div class="container" data-page="customer-networking"><div class="page-title"><div><h1 data-name>Customer Networking</h1><p class="muted"><span data-code>—</span> · <span data-phone>—</span></p></div><a class="btn btn-secondary" href="/">Back</a></div><p class="muted" data-error></p><section class="card stack"><div class="form-grid"><div><label>Router ID</label><input name="router_id" inputmode="numeric" placeholder="Router ID"></div><div><label>PPPoE Username</label><input name="username" placeholder="PPPoE username"></div></div><div class="actions"><button type="button" data-live-btn>Live Rx/Tx</button><button type="button" class="btn-secondary" data-usage-btn>6-Month Usage</button><a class="btn btn-secondary" data-router target="_blank" rel="noopener" hidden>Open Router :8080</a></div><div class="card"><h3>Live Network</h3><div data-live class="muted">Not loaded.</div></div><div><h3>Usage History</h3><div data-usage class="muted">Click 6-Month Usage.</div></div></section></div></main><script src="/assets/js/customer-networking.js" defer></script></body></html>'),[$requireAuth,$customerView]);
    $router->get('/networking/mikrotik/enforcement-audit',[$mikrotikAudit,'page'],[$requireAuth,$auditView]);
    $router->get('/api/networking/mikrotik/audit',[$mikrotikAudit,'reconciliation'],[$requireAuth,$auditView]);
    $router->get('/api/networking/mikrotik/pppoe/live',[$mikrotikAudit,'live'],[$requireAuth,$auditView]);
    $router->get('/api/networking/mikrotik/pppoe/usage',[$mikrotikAudit,'usage'],[$requireAuth,$auditView]);
    $router->get('/api/mikrotik/pppoe/enforcement-audit',[$mikrotikAudit,'audit'],[$requireAuth,$auditView]);
    $router->get('/api/mikrotik/pppoe/enforcement-audit/summary',[$mikrotikAudit,'summary'],[$requireAuth,$auditView]);
    $router->post('/api/networking/mikrotik/pppoe/action',[$mikrotikManual,'execute'],[$requireAuth,$networkManage,$csrfMiddleware]);
    $router->get('/login',[$loginController,'show']);$router->post('/login',[$loginController,'login']);$router->post('/logout',[$loginController,'logout'],[$requireAuth]);
};
