<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';

$jobs = $app->jobQueue()->claim(isset($argv[1]) ? (int)$argv[1] : 20);
foreach ($jobs as $job) {
    try {
        $app->jobDispatcher()->dispatch((string)$job['type'], (array)$job['payload']);
        $app->jobDispatcher()->complete((int)$job['id']);
    } catch (Throwable $e) {
        $app->jobDispatcher()->fail((int)$job['id'], $e->getMessage());
    }
}
printf("Jobs processed: %d\n", count($jobs));
