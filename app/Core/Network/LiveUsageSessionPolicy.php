<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use InvalidArgumentException;
final class LiveUsageSessionPolicy
{
 public function __construct(private readonly int $refreshSeconds=5,private readonly int $maxDurationSeconds=120){}
 public function validate():void{if($this->refreshSeconds<5||$this->refreshSeconds>10)throw new InvalidArgumentException('Live usage refresh must be 5-10 seconds.');if($this->maxDurationSeconds<30||$this->maxDurationSeconds>300)throw new InvalidArgumentException('Live usage window must be 30-300 seconds.');}
 public function refreshSeconds():int{return$this->refreshSeconds;}
 public function maxDurationSeconds():int{return$this->maxDurationSeconds;}
}
