<?php

declare(strict_types=1);

namespace Ispluka\Core\Payments;

use RuntimeException;

final class WebhookVerifier
{
    public function __construct(private readonly string $secret) {}

    public function verify(string $payload, string $signature): bool
    {
        if ($this->secret === '' || $signature === '') return false;
        $expected = hash_hmac('sha256', $payload, $this->secret);
        return hash_equals($expected, $signature);
    }

    public function decodeJson(string $payload): array
    {
        try { $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR); }
        catch (\Throwable $e) { throw new RuntimeException('Invalid webhook payload.', 0, $e); }
        if (!is_array($data)) throw new RuntimeException('Invalid webhook payload.');
        return $data;
    }
}
