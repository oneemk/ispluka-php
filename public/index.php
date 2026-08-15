<?php

declare(strict_types=1);

use Ispluka\Core\Http\Response;

// cPanel/LiteSpeed can route static files through the front controller when
// the document root or rewrite configuration is not pointing directly at
// /public. Serve real public assets here as a safe fallback.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$assetPath = realpath(__DIR__ . $requestPath);
$publicRoot = realpath(__DIR__);

if (
    $requestPath !== '/' &&
    $assetPath !== false &&
    $publicRoot !== false &&
    str_starts_with($assetPath, $publicRoot . DIRECTORY_SEPARATOR) &&
    is_file($assetPath)
) {
    $mimeTypes = [
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
    ];

    $extension = strtolower(pathinfo($assetPath, PATHINFO_EXTENSION));
    if (isset($mimeTypes[$extension])) {
        header('Content-Type: ' . $mimeTypes[$extension]);
    } else {
        header('Content-Type: application/octet-stream');
    }

    header('Cache-Control: public, max-age=86400');
    readfile($assetPath);
    exit;
}

$app = require dirname(__DIR__) . '/bootstrap/app.php';

// The health route is intentionally minimal until the full route configuration is introduced.
// The application will return a 404 until routes are registered in a later bootstrap step.
$app->run();
