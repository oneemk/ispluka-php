<?php

declare(strict_types=1);
namespace Ispluka\Core\Api;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
final class ApiRouter {
 private array $routes=[];
 public function get(string $path,callable $handler):void{$this->routes['GET'][$path]=$handler;}
 public function post(string $path,callable $handler):void{$this->routes['POST'][$path]=$handler;}
 public function put(string $path,callable $handler):void{$this->routes['PUT'][$path]=$handler;}
 public function delete(string $path,callable $handler):void{$this->routes['DELETE'][$path]=$handler;}
 public function dispatch(Request $request):Response { $path=parse_url($request->uri(),PHP_URL_PATH) ?: '/'; $handler=$this->routes[$request->method()][$path]??null; return $handler ? $handler($request) : ApiResponse::error('API route not found.',404); }
}
