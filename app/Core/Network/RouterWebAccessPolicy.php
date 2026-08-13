<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use InvalidArgumentException;
final readonly class RouterWebAccessPolicy
{
 public function authorize(bool $canManageRouter,bool $routerEnabled):void{if(!$canManageRouter)throw new InvalidArgumentException('Router management permission is required.');if(!$routerEnabled)throw new InvalidArgumentException('Router is disabled.');}
 public function target(string $host,int $port=8080):RouterWebTarget{return RouterWebTarget::fromConfiguredHost($host,$port);}
}
