<?php

declare(strict_types=1);

use Ispluka\Core\Http\Response;
use Ispluka\Core\Routing\Router;

return static function (Router $router): void {
    $router->get('/', static fn (): Response => Response::text('ISPLUKA application bootstrap is running.'));
};
