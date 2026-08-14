<?php

declare(strict_types=1);

use Ispluka\Core\Automation\SuspensionPolicyService;
use Ispluka\Core\Database\Database;
use Ispluka\Core\Environment;
use Ispluka\Core\Network\NetworkJobService;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$root = dirname(__DIR__, 2);
$env = $root . '/.env';
if (is_file($env)) {
    Environment::load($env);
}

// Shared-hosting safe lock: prevents overlapping enforcement cron executions.
$lockPath = sys_get_temp_dir() . '/ispluka-overdue-enforcement.lock';
$lock = fopen($lockPath, 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    fwrite(STDOUT, "Overdue enforcement skipped: another run is active.\n");
    exit(0);
}

try {
    $database = new Database(require $root . '/config/database.php');
    $jobs = new NetworkJobService($database);
    $policy = new SuspensionPolicyService($database, $jobs);

    // Enforcement eligibility is decided from billing policy + overdue invoices.
    // The script only queues network work; the existing network worker performs
    // the MikroTik operation, keeping this cron request short on shared hosting.
    $result = $policy->enforce(200);
    printf("Overdue enforcement queued: %d\n", (int)($result['suspended_jobs'] ?? 0));
} catch (Throwable $e) {
    fwrite(STDERR, "Overdue enforcement failed: " . $e->getMessage() . "\n");
    exit(1);
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
