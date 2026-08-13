<?php

declare(strict_types=1);

namespace Ispluka\Services;

use Ispluka\Repositories\CustomerServiceRepository;
use InvalidArgumentException;

final class CustomerAccessService
{
    private const TYPES = ['pppoe', 'hotspot'];
    private const STATUSES = ['active', 'suspended', 'expired', 'terminated'];

    public function __construct(private readonly CustomerServiceRepository $services)
    {
    }

    public function list(int $tenantId, int $customerId): array
    {
        $this->assertTenant($tenantId);
        return $this->services->list($tenantId, $customerId);
    }

    public function create(int $tenantId, int $customerId, array $data): int
    {
        $this->assertTenant($tenantId);
        $type = (string) ($data['service_type'] ?? '');
        if (!in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Service type must be pppoe or hotspot.');
        }
        $startDate = (string) ($data['start_date'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            throw new InvalidArgumentException('Invalid start date.');
        }
        if (($data['username'] ?? null) === null && $type === 'pppoe') {
            throw new InvalidArgumentException('PPPoE username is required.');
        }
        return $this->services->create($tenantId, $customerId, [
            ...$data,
            'service_type' => $type,
            'start_date' => $startDate,
            'auto_suspend' => (bool) ($data['auto_suspend'] ?? true),
        ]);
    }

    public function changeStatus(int $tenantId, int $serviceId, string $status): void
    {
        $this->assertTenant($tenantId);
        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid service status.');
        }
        if (!$this->services->updateStatus($tenantId, $serviceId, $status)) {
            throw new InvalidArgumentException('Service not found.');
        }
    }

    private function assertTenant(int $tenantId): void
    {
        if ($tenantId <= 0) throw new InvalidArgumentException('Tenant context is required.');
    }
}
