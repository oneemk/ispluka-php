<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use RuntimeException;
final class PppoeEnforcementRetryService
{
 public function __construct(private readonly PppoeEnforcementRetryGuard $guard,private readonly PppoeSafeEnforcer $executor,private readonly PppoeEnforcementLogger $logger,private readonly PppoeEnforcementStateDiff $diff){}
 public function retry(PppoeEnforcementOperation $op,string $previousStatus,int $attempts,bool $canRetry,?int $actorId=null):array{$this->guard->authorize($previousStatus,$canRetry,$attempts);try{$result=$this->executor->execute($op);$status=PppoeEnforcementVerifiedStatus::from($result['before'],$result['after'],$op->action,$op->targetProfile);$this->logger->record($op,$status,null,$actorId);return$result+['status'=>$status,'state_diff'=>$this->diff->compare($result['before'],$result['after'])];}catch(\Throwable $e){$this->logger->record($op,PppoeEnforcementVerifiedStatus::FAILED,$e->getMessage(),$actorId);throw new RuntimeException('Retry failed: '.$e->getMessage(),0,$e);}}
}
