<?php

declare(strict_types=1);
namespace Ispluka\Core\Payments;
interface GatewayContract { public function initiate(array $payment,array $customer):array; public function verify(array $payload):array; public function refund(string $transactionId,int $amount):array; }
