<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class RouterActivityCollectorPlan
{
 public function __construct(public int $intervalSeconds=300,public int $maxRetries=3,public int $retryBackoffSeconds=30){}
 public function validate():void{if($this->intervalSeconds<60)throw new \InvalidArgumentException('Collector interval must be at least 60 seconds.');if($this->maxRetries<0||$this->maxRetries>5)throw new \InvalidArgumentException('Retry count must be 0-5.');if($this->retryBackoffSeconds<5||$this->retryBackoffSeconds>600)throw new \InvalidArgumentException('Retry backoff must be 5-600 seconds.');}
 public function nextRetryDelay(int $attempt):int{return min($this->retryBackoffSeconds*(2**max(0,$attempt-1)),600);}
}
