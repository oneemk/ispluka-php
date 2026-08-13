<?php

declare(strict_types=1);

use Ispluka\Core\Application;
use Ispluka\Core\Exceptions\Handler;
use Ispluka\Core\Routing\Router;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$router = new Router();
$exceptionHandler = new Handler();

return new Application($router, $exceptionHandler);
