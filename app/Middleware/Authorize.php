<?php

declare(strict_types=1);

namespace Ispluka\Middleware;

use Ispluka\Core\Auth\Authorization;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;

final class Authorize
{
    public function __construct(private readonly Authorization $authorization)
    {
    }

    public function permission(string $permission): callable
    {
        return function (Request $request, callable $next) use ($permission): Response {
            if (!$this->authorization->can($permission)) {
                return Response::text('Forbidden', 403);
            }

            return $next($request);
        };
    }

    public function role(string $roleCode): callable
    {
        return function (Request $request, callable $next) use ($roleCode): Response {
            if (!$this->authorization->hasRole($roleCode)) {
                return Response::text('Forbidden', 403);
            }

            return $next($request);
        };
    }
}
