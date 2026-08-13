<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class PppoeSuspensionOperation
{
 public function __construct(public int $tenantId,public int $routerId,public string $username,public string $action,public ?string $targetProfile,public ?string $originalProfile,public string $reason){}
 public function isFallbackDisable():bool{return $this->action==='disable'&&$this->reason==='billing_overdue_no_suspend_profile';}
}
