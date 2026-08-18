<?php

declare(strict_types=1);

use Ispluka\Controllers\Auth\LoginController;
use Ispluka\Controllers\Auth\SignupController;
use Ispluka\Controllers\CollectionController;
use Ispluka\Controllers\CustomerController;
use Ispluka\Controllers\CustomerServiceController;
use Ispluka\Controllers\DashboardController;
use Ispluka\Controllers\HotspotApiController;
use Ispluka\Controllers\MikrotikEnforcementAuditController;
use Ispluka\Controllers\MikrotikManualActionController;
use Ispluka\Controllers\MikrotikRouterController;
use Ispluka\Controllers\SubscriptionController;
use Ispluka\Controllers\TenantController;
use Ispluka\Core\Application;
use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Auth\Authorization;
use Ispluka\Core\Auth\RoleManager;
use Ispluka\Core\Auth\Session;
use Ispluka\Core\Dashboard\DashboardService;
use Ispluka\Core\Database\Database;
use Ispluka\Core\Environment;
use Ispluka\Core\Exceptions\Handler;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
use Ispluka\Core\Hotspot\HotspotActionService;
use Ispluka\Core\Hotspot\HotspotAuditService;
use Ispluka\Core\Hotspot\HotspotCrudService;
use Ispluka\Core\Hotspot\HotspotRepository;
use Ispluka\Core\Hotspot\HotspotValidityService;
use Ispluka\Core\Hotspot\RouterOsHotspotGateway;
use Ispluka\Core\Network\MikrotikAutomationService;
use Ispluka\Core\Network\MikrotikConnectionClient;
use Ispluka\Core\Network\RouterOsApiClient;
use Ispluka\Core\Network\RouterOsSshClient;
use Ispluka\Core\Routing\Router;
use Ispluka\Core\Security\Csrf;
use Ispluka\Core\Security\Encryption;
use Ispluka\Core\Security\SecretBox;
use Ispluka\Middleware\Authorize;
use Ispluka\Middleware\SubscriptionGuard;
use Ispluka\Repositories\CustomerRepository;
use Ispluka\Repositories\CustomerServiceRepository;
use Ispluka\Repositories\RouterRepository;
use Ispluka\Services\CustomerAccessService;
use Ispluka\Services\CustomerService;
use Ispluka\Services\RouterService;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
$environmentFile = $root . '/.env';
if (is_file($environmentFile)) Environment::load($environmentFile);

$database = new Database(require $root . '/config/database.php');
$session = new Session();
$auth = new AuthManager($database, $session);
$authorization = new Authorization($database, $auth);
$authorize = new Authorize($authorization);
$csrf = new Csrf($session);
$encryption = new Encryption((string) ($_ENV['APP_KEY'] ?? ''));
$secretBox = new SecretBox((string) ($_ENV['APP_KEY'] ?? ''));
$router = new Router();
$exceptionHandler = new Handler();

$loginController = new LoginController($auth, $session, $csrf);
$signupController = new SignupController($database, new RoleManager($database), $csrf);
$subscriptionController = new SubscriptionController($database, $auth, $csrf);
$subscriptionGuard = new SubscriptionGuard($database, $auth);
$dashboardController = new DashboardController(new DashboardService($database), $auth, $csrf);
$collectionController = new CollectionController($database, $auth, $csrf);
$customerController = new CustomerController(new CustomerService(new CustomerRepository($database)), $auth);
$customerServiceController = new CustomerServiceController(new CustomerAccessService(new CustomerServiceRepository($database)), $auth, $encryption);
$mikrotikEnforcementAuditController = new MikrotikEnforcementAuditController($database->pdo(), $auth);

$mikrotikApiClient = new RouterOsApiClient();
$mikrotikSshClient = new RouterOsSshClient();
$mikrotikClient = new MikrotikConnectionClient($mikrotikApiClient, $mikrotikSshClient);
$automation = new MikrotikAutomationService($database, $secretBox, $mikrotikClient);
$mikrotikManualActionController = new MikrotikManualActionController($auth, $automation);
$routerService = new RouterService(new RouterRepository($database), $secretBox, $mikrotikClient);
$mikrotikRouterController = new MikrotikRouterController($routerService, $auth, $csrf);
$tenantController = new TenantController($database, $auth, new RoleManager($database), $csrf);

$hotspotRepository = new HotspotRepository($database->pdo());
$hotspotValidity = new HotspotValidityService($database);
$hotspotCrud = new HotspotCrudService($database->pdo(), $hotspotValidity);
$hotspotGateway = new RouterOsHotspotGateway(new RouterRepository($database), $secretBox, $mikrotikClient);
$hotspotAudit = new HotspotAuditService($database->pdo());
$hotspotActions = new HotspotActionService($database->pdo(), $hotspotGateway, $hotspotAudit);
$hotspotController = new HotspotApiController($hotspotRepository, $hotspotCrud, $hotspotActions, $auth);

$csrfMiddleware = static function (Request $request, callable $next) use ($csrf): Response {
    if (!$csrf->validate($request->input('_csrf'))) return Response::json(['error' => ['message' => 'Invalid CSRF token.']], 419);
    return $next($request);
};

$webRoutes = $root . '/routes/web.php';
if (is_file($webRoutes)) {
    $registerRoutes = require $webRoutes;
    if (is_callable($registerRoutes)) {
        $registerRoutes($router, $loginController, $signupController, $subscriptionController, $subscriptionGuard, $auth, $csrf, $authorize, $dashboardController, $collectionController, $customerController, $customerServiceController, $csrfMiddleware, $mikrotikEnforcementAuditController, $mikrotikManualActionController, $tenantController, $mikrotikRouterController, $hotspotController);
    }
}

return new Application($router, $exceptionHandler);
