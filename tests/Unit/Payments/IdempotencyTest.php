<?php

declare(strict_types=1);

use Ispluka\Core\Payments\Idempotency;

it('rejects empty idempotency keys', function (): void {
    expect(fn () => (new Idempotency(testDatabase()))->claim(1, '', 'payment'))->toThrow(RuntimeException::class);
});
