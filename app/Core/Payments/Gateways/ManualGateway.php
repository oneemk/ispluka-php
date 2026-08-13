<?php

declare(strict_types=1);

namespace Ispluka\Core\Payments\Gateways;

final class ManualGateway extends AbstractGateway
{
    public function initiate(array $payment): array
    {
        return ['status' => 'pending', 'reference' => 'MAN-' . bin2hex(random_bytes(10)), 'raw' => []];
    }

    public function verify(string $reference): array
    {
        return ['status' => 'pending', 'reference' => $reference, 'raw' => []];
    }
}
