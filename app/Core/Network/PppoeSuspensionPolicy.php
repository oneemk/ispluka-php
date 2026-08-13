<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class PppoeSuspensionPolicy
{
 public function decide(bool $paymentCleared,bool $overdue,?string $suspendProfile,?string $originalProfile,bool $currentEnabled):array
 {
  if($paymentCleared)return ['action'=>$currentEnabled?'none':'enable','target_profile'=>$originalProfile,'state'=>PppoeEnforcementState::ENABLED,'reason'=>'payment_cleared'];
  if(!$overdue)return ['action'=>'none','target_profile'=>$originalProfile,'state'=>PppoeEnforcementState::ENABLED,'reason'=>null];
  if($suspendProfile!==null&&trim($suspendProfile)!=='')return ['action'=>'apply_suspend_profile','target_profile'=>trim($suspendProfile),'state'=>PppoeEnforcementState::TEMPORARY_DISABLED,'reason'=>'billing_overdue'];
  return ['action'=>'disable','target_profile'=>null,'state'=>PppoeEnforcementState::TEMPORARY_DISABLED,'reason'=>'billing_overdue_no_suspend_profile'];
 }
}
