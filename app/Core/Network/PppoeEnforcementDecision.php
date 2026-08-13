<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class PppoeEnforcementDecision
{
 public static function fromBilling(bool $paymentCleared,bool $shouldSuspend,bool $routerEnabled):PppoeEnforcementState{$target=$paymentCleared||!$shouldSuspend?PppoeEnforcementState::ENABLED:PppoeEnforcementState::TEMPORARY_DISABLED;if(!$routerEnabled&&$target===PppoeEnforcementState::ENABLED)$target=PppoeEnforcementState::UNKNOWN;return new PppoeEnforcementState(0,0,'',$target,$shouldSuspend?'billing':'payment_cleared');}
}
