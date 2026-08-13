<?php

declare(strict_types=1);

namespace Ispluka\Services;

use Ispluka\Repositories\CustomerRepository;
use InvalidArgumentException;

final class CustomerService
{
    public function __construct(private readonly CustomerRepository $customers)
    {
    }

    public function list(int $tenantId, int $page, int $perPage, ?string $search): array
    {
        $this->assertTenant($tenantId);
        return $this->customers->paginate($tenantId, $page, $perPage, $search);
    }

    public function get(int $tenantId, int $customerId): array
    {
        $this->assertTenant($tenantId);
        $customer = $this->customers->find($tenantId, $customerId);
        if ($customer === null) {
            throw new InvalidArgumentException('Customer not found.');
        }
        return $customer;
    }

    public function create(int $tenantId, array $data): int
    {
        $this->assertTenant($tenantId);
        $name = trim((string) ($data['name'] ?? ''));
        $code = trim((string) ($data['customer_code'] ?? ''));
        if ($name === '' || $code === '') {
            throw new InvalidArgumentException('Customer name and customer code are required.');
        }
        if (strlen($code) > 80 || strlen($name) > 160) {
            throw new InvalidArgumentException('Customer name or code is too long.');
        }
        $billingDay = (int) ($data['billing_day'] ?? 1);
        if ($billingDay < 1 || $billingDay > 28) {
            throw new InvalidArgumentException('Billing day must be between 1 and 28.');
        }
        return $this->customers->create($tenantId, [
            ...$data,
            'name' => $name,
            'customer_code' => $code,
            'billing_day' => $billingDay,
        ]);
    }

    public function update(int $tenantId, int $customerId, array $data): void
    {
        $this->assertTenant($tenantId);
        if (isset($data['name']) && trim((string) $data['name']) === '') {
            throw new InvalidArgumentException('Customer name cannot be empty.');
        }
        if (isset($data['billing_day']) && ((int) $data['billing_day'] < 1 || (int) $data['billing_day'] > 28)) {
            throw new InvalidArgumentException('Billing day must be between 1 and 28.');
        }
        if (!$this->customers->update($tenantId, $customerId, $data)) {
            throw new InvalidArgumentException('Customer not found or no changes supplied.');
        }
    }

    public function delete(int $tenantId, int $customerId): void
    {
        $this->assertTenant($tenantId);
        if (!$this->customers->softDelete($tenantId, $customerId)) {
            throw new InvalidArgumentException('Customer not found.');
        }
    }

    private function assertTenant(int $tenantId): void
    {
        if ($tenantId <= 0) {
            throw new InvalidArgumentException('A valid tenant context is required.');
        }
    }
}
