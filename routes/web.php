<?php

declare(strict_types=1);

use Ispluka\Controllers\Auth\LoginController;
use Ispluka\Controllers\Auth\SignupController;
use Ispluka\Controllers\CollectionController;
use Ispluka\Controllers\CustomerController;
use Ispluka\Controllers\CustomerServiceController;
use Ispluka\Controllers\DashboardController;
use Ispluka\Controllers\MikrotikEnforcementAuditController;
use Ispluka\Controllers\MikrotikManualActionController;
use Ispluka\Controllers\MikrotikRouterController;
use Ispluka\Controllers\SubscriptionController;
use Ispluka\Controllers\TenantController;
use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
use Ispluka\Core\Routing\Router;
use Ispluka\Core\Security\Csrf;
use Ispluka\Middleware\Authorize;
use Ispluka\Middleware\RequireAuthentication;
use Ispluka\Middleware\SubscriptionGuard;

return static function (
    Router $router,
    LoginController $loginController,
    SignupController $signupController,
    SubscriptionController $subscriptions,
    SubscriptionGuard $subscriptionGuard,
    AuthManager $auth,
    Csrf $csrf,
    Authorize $authorize,
    DashboardController $dashboardController,
    CollectionController $collections,
    CustomerController $customers,
    CustomerServiceController $customerServices,
    callable $csrfMiddleware,
    MikrotikEnforcementAuditController $mikrotikAudit,
    MikrotikManualActionController $mikrotikManual,
    TenantController $tenants,
    MikrotikRouterController $mikrotikRouters,
): void {
    $authentication = new RequireAuthentication($auth);
    $requireAuth = static function (Request $request, callable $next) use ($authentication, $subscriptionGuard): Response {
        return $authentication($request, static fn (Request $r): Response => $subscriptionGuard($r, $next));
    };

    $router->get('/login', [$loginController, 'show']);
    $router->post('/login', [$loginController, 'login']);
    $router->get('/signup', [$signupController, 'show']);
    $router->post('/signup', [$signupController, 'store']);
    $router->get('/', [$dashboardController, 'page'], [$requireAuth]);

    $masterOnly = $authorize->role('master_admin');
    $routerView = $authorize->permission('routers.view');
    $routerManage = $authorize->permission('routers.manage');
    $router->get('/admin', static fn (): Response => Response::redirect('/admin/tenants'), [$requireAuth, $masterOnly]);
    $router->get('/admin/tenants', [$tenants, 'page'], [$requireAuth, $masterOnly]);
    $router->post('/admin/tenants', [$tenants, 'store'], [$requireAuth, $masterOnly, $csrfMiddleware]);
    $router->get('/admin/subscriptions', [$subscriptions, 'page'], [$requireAuth, $masterOnly]);
    $router->post('/admin/subscriptions', [$subscriptions, 'extend'], [$requireAuth, $masterOnly, $csrfMiddleware]);

    $customerView = $authorize->permission('customers.view');
    $customerCreate = $authorize->permission('customers.create');
    $customerUpdate = $authorize->permission('customers.update');
    $customerDelete = $authorize->permission('customers.delete');
    $paymentView = $authorize->permission('payments.view');
    $paymentManage = $authorize->permission('payments.manage');
    $reportView = $authorize->permission('reports.view');
    $serviceView = $authorize->permission('services.view');
    $serviceManage = $authorize->permission('services.manage');

    $router->get('/collection', [$collections, 'collectionPage'], [$requireAuth, $paymentManage]);
    $router->get('/reports/collection', [$collections, 'reportPage'], [$requireAuth, $reportView]);
    $router->get('/customers/create', [$collections, 'customerCreatePage'], [$requireAuth, $customerCreate]);
    $router->get('/customers', [$collections, 'customerSearchPage'], [$requireAuth, $customerView]);
    $router->get('/api/collection/invoices', [$collections, 'customerInvoices'], [$requireAuth, $paymentView]);
    $router->post('/api/collection', [$collections, 'collect'], [$requireAuth, $paymentManage, $csrfMiddleware]);

    $router->get('/api/customers', [$customers, 'index'], [$requireAuth, $customerView]);
    $router->get('/api/customer', [$customers, 'show'], [$requireAuth, $customerView]);
    $router->post('/api/customers', [$customers, 'store'], [$requireAuth, $customerCreate, $csrfMiddleware]);
    $router->post('/api/customer/update', [$customers, 'update'], [$requireAuth, $customerUpdate, $csrfMiddleware]);
    $router->post('/api/customer/delete', [$customers, 'destroy'], [$requireAuth, $customerDelete, $csrfMiddleware]);
    $router->get('/api/customer-services', [$customerServices, 'index'], [$requireAuth, $serviceView]);
    $router->post('/api/customer-services', [$customerServices, 'store'], [$requireAuth, $serviceManage, $csrfMiddleware]);
    $router->post('/api/customer-service/status', [$customerServices, 'status'], [$requireAuth, $serviceManage, $csrfMiddleware]);

    $router->get('/networking/mikrotik/routers', [$mikrotikRouters, 'page'], [$requireAuth, $routerView]);
    $router->get('/api/networking/mikrotik/routers', [$mikrotikRouters, 'index'], [$requireAuth, $routerView]);
    $router->get('/api/networking/mikrotik/routers/status', [$mikrotikRouters, 'status'], [$requireAuth, $routerView]);
    $router->post('/api/networking/mikrotik/routers', [$mikrotikRouters, 'store'], [$requireAuth, $routerManage, $csrfMiddleware]);
    $router->post('/api/networking/mikrotik/routers/update', [$mikrotikRouters, 'update'], [$requireAuth, $routerManage, $csrfMiddleware]);
    $router->post('/api/networking/mikrotik/routers/test', [$mikrotikRouters, 'test'], [$requireAuth, $routerManage, $csrfMiddleware]);
    $router->post('/api/networking/mikrotik/routers/delete', [$mikrotikRouters, 'delete'], [$requireAuth, $routerManage, $csrfMiddleware]);

    $auditView = $authorize->permission('routers.view');
    $networkManage = $authorize->permission('routers.manage');
    $router->get('/networking/customer', static fn (): Response => Response::text('<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#2563eb"><link rel="stylesheet" href="/assets/css/app.css"><title>Customer Networking</title></head><body><main class="main"><div class="container" data-page="customer-networking"><div class="page-title"><div><h1 data-name>Customer Networking</h1><p class="muted"><span data-code>—</span> · <span data-phone>—</span></p></div><a class="btn btn-secondary" href="/">Back</a></div><p class="muted" data-error></p><section class="card stack"><div class="form-grid"><div><label>Router ID</label><input name="router_id" inputmode="numeric" placeholder="Router ID"></div><div><label>PPPoE Username</label><input name="username" placeholder="PPPoE username"></div></div><div class="actions"><button type="button" data-live-btn>Live Rx/Tx</button><button type="button" class="btn-secondary" data-usage-btn>6-Month Usage</button><a class="btn btn-secondary" data-router target="_blank" rel="noopener" hidden>Open Router :8080</a></div><div class="card"><h3>Live Network</h3><div data-live class="muted">Not loaded.</div></div><div><h3>Usage History</h3><div data-usage class="muted">Click 6-Month Usage.</div></div></section></div></main><script src="/assets/js/customer-networking.js" defer></script></body></html>'), [$requireAuth, $customerView]);
    $router->get('/networking/mikrotik/enforcement-audit', [$mikrotikAudit, 'page'], [$requireAuth, $auditView]);
    $router->get('/api/networking/mikrotik/audit', [$mikrotikAudit, 'reconciliation'], [$requireAuth, $auditView]);
    $router->get('/api/networking/mikrotik/pppoe/live', [$mikrotikAudit, 'live'], [$requireAuth, $auditView]);
    $router->get('/api/networking/mikrotik/pppoe/usage', [$mikrotikAudit, 'usage'], [$requireAuth, $auditView]);
    $router->get('/api/mikrotik/pppoe/enforcement-audit', [$mikrotikAudit, 'audit'], [$requireAuth, $auditView]);
    $router->get('/api/mikrotik/pppoe/enforcement-audit/summary', [$mikrotikAudit, 'summary'], [$requireAuth, $auditView]);
    $router->post('/api/networking/mikrotik/pppoe/action', [$mikrotikManual, 'execute'], [$requireAuth, $networkManage, $csrfMiddleware]);
    $router->get('/subscription', [$subscriptions, 'page'], [$requireAuth]);
    $router->post('/logout', [$loginController, 'logout'], [$requireAuth]);
};
