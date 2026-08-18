<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use Ispluka\Core\Hotspot\ValidityDuration;

$cases = [
    '15d' => 1296000,
    '20h' => 72000,
    '90m' => 5400,
    '2d 12h' => 216000,
    '1d 6h 30m' => 109800,
];

foreach ($cases as $input => $expected) {
    $duration = ValidityDuration::parse($input);
    if ($duration->seconds !== $expected) {
        throw new RuntimeException("Duration assertion failed for {$input}.");
    }
}

foreach (['', '0d', '-1d', '15d 15d', 'abc'] as $invalid) {
    try {
        ValidityDuration::parse($invalid);
        throw new RuntimeException("Invalid duration was accepted: {$invalid}");
    } catch (InvalidArgumentException) {
        // Expected.
    }
}

echo "Hotspot ValidityDuration smoke test: PASS\n";
