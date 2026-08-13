<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class UsageAggregationPolicy
{
 public function __construct(public int $rawRetentionHours=48,public int $historyMonths=6,public int $aggregateMinutes=60){}
 public function validate():void{if($this->rawRetentionHours<1||$this->rawRetentionHours>168)throw new \InvalidArgumentException('Raw retention must be 1-168 hours.');if($this->historyMonths<1||$this->historyMonths>12)throw new \InvalidArgumentException('History retention must be 1-12 months.');if(!in_array($this->aggregateMinutes,[15,30,60,1440],true))throw new \InvalidArgumentException('Unsupported aggregation interval.');}
}
