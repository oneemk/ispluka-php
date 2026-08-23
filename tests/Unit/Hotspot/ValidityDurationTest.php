<?php

declare(strict_types=1);

namespace Tests\Unit\Hotspot;

use Ispluka\Core\Hotspot\ValidityDuration;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ValidityDurationTest extends TestCase
{
    public function testParsesFlexibleHotspotValidity(): void
    {
        self::assertSame(950400, ValidityDuration::parse('11d')->seconds);
        self::assertSame(72000, ValidityDuration::parse('20h')->seconds);
        self::assertSame(196200, ValidityDuration::parse('2d 6h 30m')->seconds);
    }

    public function testNormalizesWhitespaceAndCase(): void
    {
        self::assertSame('2d 6h', ValidityDuration::parse(' 2D   6H ')->normalized);
    }

    public function testRejectsInvalidAndDuplicateUnits(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ValidityDuration::parse('10x');
    }

    public function testRejectsDuplicateUnits(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ValidityDuration::parse('2d 3d');
    }

    public function testRejectsZeroUnits(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ValidityDuration::parse('0h');
    }
}
