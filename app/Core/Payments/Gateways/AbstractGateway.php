<?php

declare(strict_types=1);

namespace Ispluka\Core\Payments\Gateways;

use Ispluka\Core\Payments\PaymentGatewayInterface;
use RuntimeException;

abstract class AbstractGateway implements PaymentGatewayInterface
{
    public function __construct(protected readonly array $config) {}

    protected function required(string $key): string
    {
        $value = trim((string) ($this->config[$key] ?? ''));
        if ($value === '') throw new RuntimeException('Payment gateway configuration is incomplete.');
        return $value;
    }

    protected function response(string $status, array $data = []): array
    {
        $reference = trim((string) ($data['transaction_id'] ?? $data['reference'] ?? ''));
        $raw = is_array($data['raw'] ?? null) ? $data['raw'] : $data;
        return [
            'status' => $status,
            'reference' => $reference,
            'transaction_id' => $reference,
            'amount' => (int) ($data['amount'] ?? 0),
            'raw' => $raw,
            ...$data,
        ];
    }

    public function refund(string $reference, int $amountMinor): array
    {
        throw new RuntimeException('Refund is not implemented for this gateway adapter.');
    }
}
