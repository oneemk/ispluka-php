<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class PppoeEnforcementState
{
 public const ENABLED='enabled';
 public const TEMPORARY_DISABLED='temporary_disabled';
 public const UNKNOWN='unknown';
 public function __construct(public int $tenantId,public int $routerId,public string $username,public string $state,public ?string $reason=null){}
}
