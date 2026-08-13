<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use DateTimeImmutable;
final class PppoeOfflineReconciler
{
 public function __construct(private readonly PppoeActivityRepository $repository){}
 /** @param array<string,PppoeActivityState> $known */
 public function missingFromSnapshot(int $tenantId,int $routerId,array $known,RouterOsPppActiveSnapshot $snapshot,DateTimeImmutable $now):int
 {
  $seen=$snapshot->byUsername();$changed=0;
  foreach($known as $username=>$state){if($state->routerId!==$routerId||isset($seen[$username]))continue;
   $offline=new PppoeActivityState($tenantId,$routerId,$username,false,$state->activeIp,$state->lastSeenAt,$state->uptimeSeconds,$state->rxBytes,$state->txBytes,null,null,false);
   $this->repository->upsert($offline);$changed++;
  }
  return$changed;
 }
}
