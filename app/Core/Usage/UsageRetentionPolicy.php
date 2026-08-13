<?php

declare(strict_types=1);
namespace Ispluka\Core\Usage;
final readonly class UsageRetentionPolicy
{
 public function __construct(public int $snapshotMinutes=15,public int $rawRetentionHours=48,public int $aggregateRetentionDays=184){}
 public function shouldPoll(int $lastPollAt,int $now):bool{return $lastPollAt<=0||($now-$lastPollAt)>=($this->snapshotMinutes*60);}
}
