<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class PppoeUsageDelta
{
 public function __construct(public int $rxBytes,public int $txBytes,public bool $counterReset=false){}
 public static function fromCounters(?int $previousRx,?int $previousTx,?int $currentRx,?int $currentTx):self{if($currentRx===null||$currentTx===null)return new self(0,0);if($previousRx===null||$previousTx===null)return new self(0,0,true);if($currentRx<$previousRx||$currentTx<$previousTx)return new self(0,0,true);return new self($currentRx-$previousRx,$currentTx-$previousTx);}
}
