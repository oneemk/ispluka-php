<?php

declare(strict_types=1);

namespace Ispluka\Core\Notifications\Channels;

use Ispluka\Core\Notifications\NotificationChannelInterface;
use RuntimeException;

final class EmailChannel implements NotificationChannelInterface
{
    public function __construct(private readonly string $fromAddress) {}

    public function send(array $message): array
    {
        $to = trim((string)($message['recipient'] ?? ''));
        $subject = trim((string)($message['subject'] ?? ''));
        $body = (string)($message['body'] ?? '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL) || $subject === '' || $body === '') {
            throw new RuntimeException('Invalid email notification.');
        }
        $headers = 'From: ' . $this->fromAddress . "\r\n" . 'Content-Type: text/plain; charset=UTF-8';
        $accepted = @mail($to, $subject, $body, $headers);
        return ['status' => $accepted ? 'accepted' : 'failed'];
    }
}
