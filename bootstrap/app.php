<?php

declare(strict_types=1);

use Ispluka\Controllers\Auth\LoginController;
use Ispluka\Controllers\CustomerController;
use Ispluka\Core\Application;
use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Auth\Authorization;
use Ispluka\Core\Auth\Session;
use Ispluka\Core\Database\Database;
use Ispluka\Core\Environment;
use Ispluka\Core\Exceptions\Handler;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
use Ispluka\Core\Routing\Router;
use Ispluka\Core\Security\Csrf;
use Ispluka\Middleware\Authorize;
use Ispluka\Repositories\CustomerRepository;
use Ispluka\Services\CustomerService;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
$environmentFile = $root . '/.env';
if (is_file($environmentFile)) {
    Environment::load($environmentFile);
}

$databaseConfig = require $root . '/config/database.php';
$database = new Database($databaseConfig);
$session = new Session();
$auth = new AuthManager($database, $session);
$authorization = new Authorization($database, $auth);
$authorize = new Authorize($authorization);
$csrf = new Csrf($session);

$router = new Router();
$exceptionHandler = new Handler();
$loginController = new LoginController($auth, $session, $csrf);
$customerController = new CustomerController(new CustomerService(new CustomerRepository($database)), $auth);

$csrfMiddleware = static function (Request $request, callable $next) use ($csrf): Response {
    if (!$csrf->validate($request->input('_csrf'))) {
        return Response::json(['error' => ['message' => 'Invalid CSRF token.']], 419);
    }
    return $next($request);
};

$webRoutes = $root . '/routes/web.php';
if (is_file($webRoutes)) {
    $registerRoutes = require $webRoutes;
    if (is_callable($registerRoutes)) {
        $registerRoutes($router, $loginController, $auth, $csrf, $authorize, $customerController, $csrfMiddleware);
    }
}

return new Application($router, $exceptionHandler);
