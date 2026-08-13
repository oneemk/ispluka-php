<?php

declare(strict_types=1);

use Ispluka\Core\Billing\BillingJob;

require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';

$batch = isset($argv[1]) ? (int)$argv[1] : 100;
$processed = $app->billingJob()->run($batch);
printf("Billing cycle processed: %d\n", $processed);
