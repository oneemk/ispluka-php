<?php

declare(strict_types=1);

namespace Ispluka\Core\Notifications;

interface NotificationChannelInterface
{
    public function send(array $message): array;
}
