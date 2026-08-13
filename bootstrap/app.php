<?php

declare(strict_types=1);

use Ispluka\Controllers\Auth\LoginController;
use Ispluka\Core\Application;
use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Auth\Session;
use Ispluka\Core\Database\Database;
use Ispluka\Core\Environment;
use Ispluka\Core\Exceptions\Handler;
use Ispluka\Core\Routing\Router;
use Ispluka\Core\Security\Csrf;

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
$csrf = new Csrf($session);

$router = new Router();
$exceptionHandler = new Handler();
$loginController = new LoginController($auth, $session, $csrf);

$webRoutes = $root . '/routes/web.php';
if (is_file($webRoutes)) {
    $registerRoutes = require $webRoutes;
    if (is_callable($registerRoutes)) {
        $registerRoutes($router, $loginController, $auth, $csrf);
    }
}

return new Application($router, $exceptionHandler);
