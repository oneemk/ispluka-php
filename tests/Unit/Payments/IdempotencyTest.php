<?php

declare(strict_types=1);

namespace Tests\Unit\Payments;

use Ispluka\Core\Database\Database;
use Ispluka\Core\Payments\Idempotency;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

final class IdempotencyTest extends TestCase
{
    public function testRejectsEmptyIdempotencyKeys(): void
    {
        $database = (new ReflectionClass(Database::class))->newInstanceWithoutConstructor();
        $idempotency = new Idempotency($database);

        $this->expectException(RuntimeException::class);
        $idempotency->claim(1, '', 'payment');
    }
}
