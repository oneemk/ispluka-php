<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use DateTimeImmutable;
final readonly class RouterSyncFreshness
{
 public function __construct(public int $maxAgeSeconds=300){}
 public function healthy(?string $lastSyncAt,DateTimeImmutable $now):bool{if($lastSyncAt===null)return false;return (new DateTimeImmutable($lastSyncAt))->getTimestamp()>=$now->getTimestamp()-$this->maxAgeSeconds;}
}
