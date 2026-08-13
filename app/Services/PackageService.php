<?php

declare(strict_types=1);

namespace Ispluka\Services;

use Ispluka\Repositories\PackageRepository;
use InvalidArgumentException;

final class PackageService
{
    public function __construct(private readonly PackageRepository $repository) {}

    public function list(int $tenantId, int $limit = 50, int $offset = 0): array
    {
        return $this->repository->listByTenant($tenantId, min(max($limit, 1), 100), max($offset, 0));
    }

    public function create(int $tenantId, array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        $code = trim((string) ($data['code'] ?? ''));
        $type = (string) ($data['service_type'] ?? 'pppoe');
        $download = (int) ($data['download_kbps'] ?? 0);
        $upload = (int) ($data['upload_kbps'] ?? 0);
        $price = (float) ($data['price'] ?? -1);
        if ($name === '' || $code === '' || !in_array($type, ['pppoe', 'hotspot', 'both'], true) || $download <= 0 || $upload <= 0 || $price < 0) {
            throw new InvalidArgumentException('Invalid package data.');
        }
        return $this->repository->create($tenantId, [
            'name' => $name, 'code' => $code, 'description' => $data['description'] ?? null,
            'service_type' => $type, 'download_kbps' => $download, 'upload_kbps' => $upload,
            'price' => number_format($price, 2, '.', ''), 'billing_period' => $data['billing_period'] ?? 'monthly',
            'validity_days' => $data['validity_days'] ?? null, 'status' => $data['status'] ?? 'active',
            'settings' => $data['settings'] ?? [],
        ]);
    }
}
