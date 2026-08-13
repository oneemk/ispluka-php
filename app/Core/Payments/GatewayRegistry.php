<?php

declare(strict_types=1);

namespace Ispluka\Core\Payments;

use InvalidArgumentException;

final class GatewayRegistry
{
    /** @var array<string, PaymentGatewayInterface> */
    private array $gateways = [];

    public function register(string $code, PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$code] = $gateway;
    }

    public function get(string $code): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$code])) {
            throw new InvalidArgumentException('Unsupported payment gateway.');
        }
        return $this->gateways[$code];
    }
}
