<?php

declare(strict_types=1);

use Ispluka\Core\Application;
use Ispluka\Core\Environment;
use Ispluka\Core\Exceptions\Handler;
use Ispluka\Core\Routing\Router;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$environmentFile = dirname(__DIR__) . '/.env';
if (is_file($environmentFile)) {
    Environment::load($environmentFile);
}

$router = new Router();
$exceptionHandler = new Handler();

$webRoutes = dirname(__DIR__) . '/routes/web.php';
if (is_file($webRoutes)) {
    $registerRoutes = require $webRoutes;
    if (is_callable($registerRoutes)) {
        $registerRoutes($router);
    }
}

return new Application($router, $exceptionHandler);
