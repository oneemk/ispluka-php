<?php

declare(strict_types=1);

namespace Ispluka\Core\Notifications\Channels;

use Ispluka\Core\Notifications\NotificationChannelInterface;
use RuntimeException;

final class SmsChannel implements NotificationChannelInterface
{
    public function __construct(private readonly string $endpoint, private readonly string $apiKey) {}

    public function send(array $message): array
    {
        $phone = trim((string)($message['recipient'] ?? ''));
        $text = trim((string)($message['body'] ?? ''));
        if ($phone === '' || $text === '' || $this->endpoint === '' || $this->apiKey === '') {
            throw new RuntimeException('SMS configuration or message is incomplete.');
        }
        return ['status' => 'queued', 'provider' => 'http', 'phone' => $phone];
    }
}
