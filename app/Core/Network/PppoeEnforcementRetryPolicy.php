<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class PppoeEnforcementRetryPolicy
{
 public function __construct(public int $maxAttempts=3,public int $backoffSeconds=5){}
 public function delays():array{$n=max(1,min(5,$this->maxAttempts));$base=max(1,min(60,$this->backoffSeconds));$out=[];for($i=1;$i<$n;$i++)$out[]=$base*$i;return$out;}
}
