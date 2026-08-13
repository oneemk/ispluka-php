<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use DateTimeImmutable;
final class PppoeActivityUpdater
{
 public function __construct(private readonly PppoeActivityRepository $repository){}
 public function apply(int $tenantId,RouterOsPppActiveSnapshot $snapshot):int
 {
  $count=0;$now=new DateTimeImmutable('now');
  foreach($snapshot->sessions as $session){
   if($session->username==='')continue;
   $state=new PppoeActivityState($tenantId,$snapshot->routerId,$session->username,true,$session->address,$now->format('c'),$session->uptimeSeconds,$session->rxBytes,$session->txBytes,$session->rxRateBps,$session->txRateBps,false);
   $this->repository->upsert($state);$count++;
  }
  return$count;
 }
}
