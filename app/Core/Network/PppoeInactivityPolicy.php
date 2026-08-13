<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use DateTimeImmutable;
final readonly class PppoeInactivityPolicy
{
 public function __construct(public int $inactiveDays=20){}
 public function isInactive(?string $lastSeenAt,DateTimeImmutable $now):bool{if($lastSeenAt===null)return true;$last=new DateTimeImmutable($lastSeenAt);return $last < $now->modify('-'.$this->inactiveDays.' days');}
 public function status(?string $lastSeenAt,bool $routerSyncHealthy,DateTimeImmutable $now):string{if(!$routerSyncHealthy)return'cannot_determine';return$this->isInactive($lastSeenAt,$now)?'inactive_20_plus':'recently_active';}
}
