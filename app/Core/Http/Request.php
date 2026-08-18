<?php

declare(strict_types=1);

namespace Ispluka\Core\Http;

final class Request
{
    public function __construct(private readonly string $method, private readonly string $path, private readonly array $query, private readonly array $input, private readonly array $server) {}

    public static function capture(): self
    {
        $method=strtoupper((string)($_SERVER['REQUEST_METHOD']??'GET'));$uri=(string)($_SERVER['REQUEST_URI']??'/');$path=parse_url($uri,PHP_URL_PATH)?:'/';$input=$_POST;
        if(!in_array($method,['GET','HEAD'],true)&&$input===[]){$raw=file_get_contents('php://input')?:'';$contentType=strtolower((string)($_SERVER['CONTENT_TYPE']??''));if(str_contains($contentType,'application/json')){$decoded=json_decode($raw,true);if(is_array($decoded))$input=$decoded;}elseif(str_contains($contentType,'application/x-www-form-urlencoded')){parse_str($raw,$input);}}
        return new self($method,'/'.trim($path,'/'),$_GET,$input,$_SERVER);
    }
    public function method():string{return$this->method;}
    public function path():string{return$this->path==='//'?'/':$this->path;}
    public function query(string$key,mixed$default=null):mixed{return$this->query[$key]??$default;}
    public function input(string$key,mixed$default=null):mixed{return$this->input[$key]??$default;}
    public function all():array{return$this->input;}
    public function server(string$key,mixed$default=null):mixed{return$this->server[$key]??$default;}
}
