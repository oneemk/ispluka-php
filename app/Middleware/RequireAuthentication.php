<?php

declare(strict_types=1);

namespace Ispluka\Middleware;

use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;

final class RequireAuthentication
{
    public function __construct(private readonly AuthManager $auth)
    {
    }

    public function __invoke(Request $request, callable $next): Response
    {
        if (!$this->auth->check()) {
            return Response::redirect('/login');
        }

        return $next($request);
    }
}
