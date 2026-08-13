<?php

declare(strict_types=1);

namespace Ispluka\Core\Routing;

use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
use RuntimeException;

final class Router
{
    /** @var array<int, array{method:string,path:string,handler:callable}> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function add(string $method, string $path, callable $handler): void
    {
        $normalized = '/' . trim($path, '/');
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $normalized === '//' ? '/' : $normalized,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method() || $route['path'] !== $request->path()) {
                continue;
            }

            $result = ($route['handler'])($request);
            return $result instanceof Response ? $result : Response::text((string) $result);
        }

        throw new RuntimeException('Route not found', 404);
    }
}
