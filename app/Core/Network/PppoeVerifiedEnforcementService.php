<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final class PppoeVerifiedEnforcementService
{
 public function __construct(private readonly PppoeSafeEnforcer $executor,private readonly PppoeEnforcementLogger $logger){}
 public function execute(PppoeEnforcementOperation $op,?int $actorId=null):array
 {
  try{$result=$this->executor->execute($op);$status=PppoeEnforcementVerifiedStatus::from($result['before'],$result['after'],$op->action,$op->targetProfile);$this->logger->record($op,$status,null,$actorId);return $result+['status'=>$status];}
  catch(\Throwable $e){$this->logger->record($op,PppoeEnforcementVerifiedStatus::FAILED,$e->getMessage(),$actorId);throw$e;}
 }
}
