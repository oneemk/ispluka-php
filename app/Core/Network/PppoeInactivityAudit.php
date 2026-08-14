<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;

final class PppoeInactivityAudit
{
    public function __construct(private readonly PppoeActivityRepository $repository) {}
    public function find(int $tenantId,int $days=20):array
    {
        return $this->repository->inactive($tenantId,max(1,$days));
    }
}
