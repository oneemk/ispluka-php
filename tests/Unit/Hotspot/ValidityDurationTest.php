<?php

declare(strict_types=1);
use Ispluka\Core\Hotspot\ValidityDuration;

it('parses flexible hotspot validity', function () {
    expect(ValidityDuration::parse('11d')->seconds)->toBe(950400);
    expect(ValidityDuration::parse('20h')->seconds)->toBe(72000);
    expect(ValidityDuration::parse('2d 6h 30m')->seconds)->toBe(196200);
});

it('normalizes whitespace and case', function () {
    expect(ValidityDuration::parse(' 2D   6H ')->normalized)->toBe('2d 6h');
});

it('rejects invalid and duplicate units', function () {
    expect(fn()=>ValidityDuration::parse('10x'))->toThrow(InvalidArgumentException::class);
    expect(fn()=>ValidityDuration::parse('2d 3d'))->toThrow(InvalidArgumentException::class);
    expect(fn()=>ValidityDuration::parse('0h'))->toThrow(InvalidArgumentException::class);
});
