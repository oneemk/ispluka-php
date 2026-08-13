<?php

declare(strict_types=1);

namespace Ispluka\Core\Payments;

interface PaymentGatewayInterface
{
    public function initiate(array $payment): array;
    public function verify(string $reference): array;
    public function refund(string $reference, int $amountMinor): array;
}
