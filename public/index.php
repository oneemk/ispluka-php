<?php

declare(strict_types=1);

use Ispluka\Core\Http\Response;

$app = require dirname(__DIR__) . '/bootstrap/app.php';

// The health route is intentionally minimal until the full route configuration is introduced.
// The application will return a 404 until routes are registered in a later bootstrap step.
$app->run();
