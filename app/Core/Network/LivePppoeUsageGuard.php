<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use InvalidArgumentException;
final readonly class LivePppoeUsageGuard
{
 public function __construct(public int $refreshSeconds=5,public int $windowSeconds=120){}
 public function validate():void{if($this->refreshSeconds<5||$this->refreshSeconds>10)throw new InvalidArgumentException('Live refresh must be 5-10 seconds.');if($this->windowSeconds<30||$this->windowSeconds>300)throw new InvalidArgumentException('Live window must be 30-300 seconds.');}
 public function cacheKey(int $routerId,string $username):string{return 'pppoe:live:'.$routerId.':'.hash('sha256',$username);}
}
