<?php

declare(strict_types=1);
use Ispluka\Core\Api\ApiRouter;
use Ispluka\Controllers\Api\HealthController;
return static function(ApiRouter $router): void { $router->get('/api/v1/health', new HealthController()); };
