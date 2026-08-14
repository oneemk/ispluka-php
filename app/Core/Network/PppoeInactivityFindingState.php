<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;

final readonly class PppoeInactivityFindingState
{
    public function __construct(public int $tenantId,public int $routerId,public string $username,public string $status,public int $observedAt){}
    public function isOpen():bool{return $this->status==='open';}
}
