<?php

declare(strict_types=1);

namespace Ispluka\Core\Routing;

use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
use RuntimeException;

final class Router
{
    /** @var array<int, array{method:string,path:string,handler:callable,middleware:list<callable>}> */
    private array $routes = [];

    public function get(string $path, callable $handler, array $middleware = []): void { $this->add('GET', $path, $handler, $middleware); }
    public function post(string $path, callable $handler, array $middleware = []): void { $this->add('POST', $path, $handler, $middleware); }
    public function patch(string $path, callable $handler, array $middleware = []): void { $this->add('PATCH', $path, $handler, $middleware); }
    public function delete(string $path, callable $handler, array $middleware = []): void { $this->add('DELETE', $path, $handler, $middleware); }

    public function add(string $method, string $path, callable $handler, array $middleware = []): void
    {
        $normalized = '/' . trim($path, '/');
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $normalized === '//' ? '/' : $normalized,
            'handler' => $handler,
            'middleware' => array_values($middleware),
        ];
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method() || $route['path'] !== $request->path()) continue;
            $pipeline = array_reduce(
                array_reverse($route['middleware']),
                static fn (callable $next, callable $middleware): callable => static fn (Request $request): Response => $middleware($request, $next),
                static fn (Request $request): Response => self::toResponse(($route['handler'])($request)),
            );
            return $pipeline($request);
        }
        throw new RuntimeException('Route not found', 404);
    }

    private static function toResponse(mixed $result): Response { return $result instanceof Response ? $result : Response::text((string) $result); }
}
