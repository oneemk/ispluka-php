<?php

declare(strict_types=1);
namespace Ispluka\Core\Api;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
final class ApiRouter {
 private array $routes=[];
 public function get(string $path,callable $handler):void{$this->add('GET',$path,$handler);}
 public function post(string $path,callable $handler):void{$this->add('POST',$path,$handler);}
 public function put(string $path,callable $handler):void{$this->add('PUT',$path,$handler);}
 public function delete(string $path,callable $handler):void{$this->add('DELETE',$path,$handler);}
 private function add(string $method,string $path,callable $handler):void{$this->routes[]=[$method,rtrim($path,'/')?:'',$handler];}
 public function dispatch(Request $request):Response{$method=strtoupper($request->method());$path=rtrim(parse_url($request->uri(),PHP_URL_PATH)?:'','/')?:'';foreach($this->routes as [$m,$pattern,$handler]){if($m!==$method)continue;$regex=preg_replace_callback('/\{([A-Za-z_][A-Za-z0-9_]*)\}/',fn($x)=>'(?P<'.$x[1].'>[^/]+)',$pattern);$regex='#^'.str_replace('/','\\/',$regex).'$#';if(preg_match($regex,$path,$matches)){ $params=[];foreach($matches as $k=>$v)if(is_string($k))$params[$k]=$v;return $handler($request,$params);}}return ApiResponse::error('API route not found.',404);}
}
