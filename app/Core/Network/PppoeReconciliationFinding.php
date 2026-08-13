<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class PppoeReconciliationFinding
{
 public const ERP_MISSING_ON_ROUTER='erp_missing_on_router';
 public const ROUTER_UNMAPPED='router_unmapped';
 public const ENABLE_STATE_MISMATCH='enable_state_mismatch';
 public const PROFILE_MISMATCH='profile_mismatch';
 public const AMBIGUOUS_MAPPING='ambiguous_mapping';
 public const INACTIVE='inactive';
 public const ZERO_OR_FREE_AUDIT='zero_or_free_audit';
 public function __construct(public int $tenantId,public int $routerId,public string $username,public string $type,public string $severity,public string $message,public array $details=[]){ }
}
