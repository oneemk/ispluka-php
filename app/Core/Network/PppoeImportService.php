<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final class PppoeImportService
{
 public function __construct(private readonly PppoeImportCandidateRepository $repository){}
 /** @param ImportedPppoeUser[] $users */
 public function import(int $tenantId,array $users):int{$count=0;foreach($users as $u){if($u->username==='')continue;$this->repository->upsert(new PppoeImportCandidate($tenantId,$u->routerId,$u->username,$u->profile,$u->activeIp,$u->callerId,null,$u->mapped?'completed':'pending'));$count++;}return$count;}
}
