<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;

final class PppoeInactivityFindingTransition
{
    public function open(int $tenantId,int $routerId,string $username,int $now):PppoeInactivityFindingState{return new PppoeInactivityFindingState($tenantId,$routerId,$username,'open',$now);}
    public function resolve(PppoeInactivityFindingState $state,int $now):PppoeInactivityFindingState{return new PppoeInactivityFindingState($state->tenantId,$state->routerId,$state->username,'resolved',$now);}
}
