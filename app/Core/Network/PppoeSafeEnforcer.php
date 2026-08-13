<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use RuntimeException;
final class PppoeSafeEnforcer
{
 /** @param callable(string,array):array $inspect */
 public function __construct(private readonly RouterOsPppEnforcer $router,private readonly $inspect){}
 public function execute(PppoeEnforcementOperation $op):array
 {
  $before=($this->inspect)($op->username);if(!is_array($before))throw new RuntimeException('Unable to verify current MikroTik PPPoE state.');
  if(($before['id']??null)===null||($before['enabled']??null)===null)throw new RuntimeException('MikroTik PPPoE user was not found or could not be resolved.');
  $id=(string)$before['id'];
  try{
   if($op->action==='disable')$this->router->disable($id);
   elseif($op->action==='enable')$this->router->enable($id);
   elseif($op->action==='apply_suspend_profile')$this->router->setProfile($id,(string)$op->targetProfile);
   elseif($op->action==='restore_profile')$this->router->setProfile($id,(string)$op->originalProfile);
   elseif($op->action!=='none')throw new RuntimeException('Unsupported PPPoE enforcement action.');
   $after=($this->inspect)($op->username);if(!is_array($after))throw new RuntimeException('Unable to verify MikroTik state after enforcement.');
   return ['before'=>$before,'after'=>$after,'verified'=>true];
  }catch(\Throwable $e){throw new RuntimeException('PPPoE enforcement execution failed: '.$e->getMessage(),0,$e);}
 }
}
