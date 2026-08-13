<?php

declare(strict_types=1);

namespace Ispluka\Core\Notifications\Channels;

use Ispluka\Core\Notifications\NotificationChannelInterface;
use Psr\Log\LoggerInterface;

final class LogChannel implements NotificationChannelInterface
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function send(array $message): array
    {
        $this->logger->info('notification', [
            'type' => $message['type'] ?? 'generic',
            'recipient' => $message['recipient'] ?? null,
            'subject' => $message['subject'] ?? null,
        ]);
        return ['status' => 'accepted'];
    }
}
