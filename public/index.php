<?php

declare(strict_types=1);

use Ispluka\Core\Http\Response;

// cPanel/LiteSpeed fallback: if a request for a real public asset reaches
// the front controller, serve the file directly instead of sending it to
// the application router.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$relativePath = ltrim(rawurldecode($requestPath), '/');
$publicRoot = realpath(__DIR__);
$assetPath = $publicRoot !== false ? realpath($publicRoot . DIRECTORY_SEPARATOR . $relativePath) : false;

if (
    $relativePath !== '' &&
    $assetPath !== false &&
    $publicRoot !== false &&
    ($assetPath === $publicRoot || str_starts_with($assetPath, $publicRoot . DIRECTORY_SEPARATOR)) &&
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
    header('Content-Type: ' . ($mimeTypes[$extension] ?? 'application/octet-stream'));
    header('Cache-Control: public, max-age=86400');
    header('X-Content-Type-Options: nosniff');
    readfile($assetPath);
    exit;
}

$app = require dirname(__DIR__) . '/bootstrap/app.php';

// The health route is intentionally minimal until the full route configuration is introduced.
// The application will return a 404 until routes are registered in a later bootstrap step.
$app->run();
