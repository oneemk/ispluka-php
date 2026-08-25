<?php

declare(strict_types=1);

namespace Ispluka\Core\Payments\Gateways;

final class ManualGateway extends AbstractGateway
{
    public function initiate(array $payment, array $customer): array
    {
        return ['status' => 'pending', 'reference' => 'MAN-' . bin2hex(random_bytes(10)), 'raw' => ['payment' => $payment, 'customer' => $customer]];
    }

    public function verify(array $payload): array
    {
        $reference = trim((string) ($payload['reference'] ?? $payload['transaction_id'] ?? ''));
        return $reference === ''
            ? $this->response('invalid')
            : $this->response('pending', ['transaction_id' => $reference, 'raw' => $payload]);
    }
}
