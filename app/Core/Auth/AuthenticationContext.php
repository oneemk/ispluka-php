<?php

declare(strict_types=1);

namespace Ispluka\Core\Auth;

final readonly class AuthenticationContext
{
    public function __construct(
        public int $userId,
        public ?int $tenantId,
    ) {
    }
}
