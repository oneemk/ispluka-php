<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use InvalidArgumentException;
final readonly class PppoeEnforcementRetryGuard
{
 public function authorize(string $status,bool $canRetry,int $attempts,int $maxAttempts=3):void{if(!$canRetry)throw new InvalidArgumentException('Retry permission is required.');if(!in_array($status,['failed','mismatch'],true))throw new InvalidArgumentException('Only Failed or Mismatch operations can be retried.');if($attempts>=$maxAttempts)throw new InvalidArgumentException('Retry limit reached.');}
}
