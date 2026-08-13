<?php

declare(strict_types=1);

use Ispluka\Controllers\Auth\LoginController;
use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Http\Response;
use Ispluka\Core\Routing\Router;
use Ispluka\Core\Security\Csrf;
use Ispluka\Middleware\RequireAuthentication;

return static function (
    Router $router,
    LoginController $loginController,
    AuthManager $auth,
    Csrf $csrf,
): void {
    $requireAuth = new RequireAuthentication($auth);

    $router->get('/', static function () use ($auth, $csrf): Response {
        $token = htmlspecialchars($csrf->token(), ENT_QUOTES, 'UTF-8');
        $userId = (int) $auth->userId();
        $logout = '<form method="post" action="/logout"><input type="hidden" name="_csrf" value="' . $token . '"><button type="submit">Sign out</button></form>';
        return Response::text('<!doctype html><html><head><meta charset="utf-8"><title>ISPLUKA</title></head><body><h1>ISPLUKA</h1><p>Authenticated user: ' . $userId . '</p>' . $logout . '</body></html>');
    }, [$requireAuth]);

    $router->get('/login', [$loginController, 'show']);
    $router->post('/login', [$loginController, 'login']);
    $router->post('/logout', [$loginController, 'logout'], [$requireAuth]);
};
