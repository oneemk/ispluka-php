<?php

declare(strict_types=1);

namespace Ispluka\Core\Notifications;

use InvalidArgumentException;

final class NotificationManager
{
    /** @var array<string, NotificationChannelInterface> */
    private array $channels = [];

    public function register(string $name, NotificationChannelInterface $channel): void
    {
        $this->channels[$name] = $channel;
    }

    public function send(string $channel, array $message): array
    {
        if (!isset($this->channels[$channel])) {
            throw new InvalidArgumentException('Unsupported notification channel.');
        }
        return $this->channels[$channel]->send($message);
    }
}
