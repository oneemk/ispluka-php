<?php

declare(strict_types=1);

// cPanel/LiteSpeed fallback: serve real public files directly when the
// request is sent through the front controller. Avoid realpath() here because
// some LiteSpeed/cPanel PHP configurations can resolve rewritten paths
// differently from the filesystem path.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$relativePath = ltrim(rawurldecode($requestPath), '/');
$publicRoot = __DIR__;

if ($relativePath !== '' && !str_contains($relativePath, "\0")) {
    $assetPath = $publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $extension = strtolower(pathinfo($assetPath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css' => 'text/css; charset=UTF-8', 'js' => 'application/javascript; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8', 'svg' => 'image/svg+xml',
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif', 'webp' => 'image/webp', 'ico' => 'image/x-icon',
        'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf',
    ];
    if (isset($mimeTypes[$extension]) && is_file($assetPath)) {
        header('Content-Type: ' . $mimeTypes[$extension]);
        header('Cache-Control: public, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        readfile($assetPath);
        exit;
    }
}

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->run();
