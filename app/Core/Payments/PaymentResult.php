<?php

declare(strict_types=1);

namespace Ispluka\Core\Payments;

final readonly class PaymentResult
{
    public function __construct(
        public string $status,
        public string $gatewayReference,
        public int $amountMinor,
        public array $raw = [],
    ) {}
}
