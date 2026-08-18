<?php

declare(strict_types=1);

use Ispluka\Core\Database\Database;
use Ispluka\Core\Environment;
use Ispluka\Core\Network\MikrotikConnectionClient;
use Ispluka\Core\Network\PppoeActivityCollector;
use Ispluka\Core\Network\PppoeActivityRepository;
use Ispluka\Core\Network\PppoeActivityRunCoordinator;
use Ispluka\Core\Network\PppoeActivityScheduler;
use Ispluka\Core\Network\RouterOsApiClient;
use Ispluka\Core\Network\RouterOsSshClient;
use Ispluka\Core\Security\SecretBox;
use PDO;
use Throwable;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$root = dirname(__DIR__, 2);
$environmentFile = $root . '/.env';
if (is_file($environmentFile)) {
    Environment::load($environmentFile);
}

$globalLockPath = sys_get_temp_dir() . '/ispluka-pppoe-activity-worker.lock';
$globalLock = fopen($globalLockPath, 'c');
if ($globalLock === false || !flock($globalLock, LOCK_EX | LOCK_NB)) {
    fwrite(STDOUT, "PPPoE activity worker skipped: another run is active.\n");
    exit(0);
}

$routerLocks = [];
$lock = static function (string $key, int $ttl) use (&$routerLocks): bool {
    $path = sys_get_temp_dir() . '/ispluka-' . sha1($key) . '.lock';
    $handle = fopen($path, 'c');
    if ($handle === false) {
        return false;
    }

    if (!flock($handle, LOCK_EX | LOCK_NB)) {
        fclose($handle);
        return false;
    }

    $routerLocks[$key] = $handle;
    return true;
};

$unlock = static function (string $key) use (&$routerLocks): void {
    $handle = $routerLocks[$key] ?? null;
    if (!is_resource($handle)) {
        return;
    }

    flock($handle, LOCK_UN);
    fclose($handle);
    unset($routerLocks[$key]);
};

try {
    $db = new Database(require $root . '/config/database.php');
    $pdo = $db->pdo();
    $secretBox = new SecretBox((string) ($_ENV['APP_KEY'] ?? ''));
    $api = new RouterOsApiClient();
    $ssh = new RouterOsSshClient();
    $client = new MikrotikConnectionClient($api, $ssh);
    $repository = new PppoeActivityRepository($pdo);

    $collector = new PppoeActivityCollector(
        $repository,
        static function (string $command, array $arguments) use ($client, $secretBox, $pdo): array {
            $routerId = (int) ($GLOBALS['ispluka_pppoe_router_id'] ?? 0);
            $router = $GLOBALS['ispluka_pppoe_router'] ?? null;
            if (!is_array($router) || $routerId < 1) {
                throw new RuntimeException('PPPoE worker router context is missing.');
            }

            $config = $router;
            $config['password'] = $secretBox->decrypt((string) ($config['encrypted_password'] ?? ''));

            try {
                $client->connect($config);
                $result = $client->command($command, $arguments);
                return $result;
            } finally {
                try {
                    $client->disconnect();
                } catch (Throwable $ignore) {
                }
            }
        }
    );

    $scheduler = new PppoeActivityScheduler($collector, $lock, $unlock, 60);
    $coordinator = new PppoeActivityRunCoordinator($scheduler, static fn(int $tenantId, int $routerId): bool => true);

    $stmt = $pdo->query("SELECT * FROM routers WHERE status NOT IN ('inactive','maintenance') ORDER BY tenant_id ASC, id ASC");
    $routers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    foreach ($routers as $router) {
        $tenantId = (int) ($router['tenant_id'] ?? 0);
        $routerId = (int) ($router['id'] ?? 0);
        if ($tenantId < 1 || $routerId < 1) {
            continue;
        }

        $GLOBALS['ispluka_pppoe_router_id'] = $routerId;
        $GLOBALS['ispluka_pppoe_router'] = $router;

        $result = $coordinator->run([
            ['tenantId' => $tenantId, 'routerId' => $routerId],
        ]);
        $item = $result[0] ?? ['status' => 'failed', 'error' => 'No worker result.'];
        $status = (string) ($item['status'] ?? 'failed');

        if ($status === 'success') {
            $pdo->prepare('UPDATE routers SET status=\'online\',last_seen_at=CURRENT_TIMESTAMP,last_error=NULL,updated_at=CURRENT_TIMESTAMP WHERE tenant_id=:t AND id=:r')
                ->execute([':t' => $tenantId, ':r' => $routerId]);
        } elseif ($status === 'failed') {
            $error = mb_substr((string) ($item['error'] ?? 'PPPoE activity sync failed.'), 0, 1000);
            $pdo->prepare('UPDATE routers SET status=\'offline\',last_error=:e,updated_at=CURRENT_TIMESTAMP WHERE tenant_id=:t AND id=:r')
                ->execute([':e' => $error, ':t' => $tenantId, ':r' => $routerId]);
        }

        $results[] = [
            'tenantId' => $tenantId,
            'routerId' => $routerId,
            'name' => (string) ($router['name'] ?? ''),
            'status' => $status,
            'sessions' => (int) ($item['sessions'] ?? 0),
            'error' => $item['error'] ?? null,
        ];
    }

    $success = count(array_filter($results, static fn(array $r): bool => $r['status'] === 'success'));
    $failed = count(array_filter($results, static fn(array $r): bool => $r['status'] === 'failed'));
    $skipped = count(array_filter($results, static fn(array $r): bool => $r['status'] === 'skipped'));

    printf("PPPoE activity sync completed: routers=%d success=%d failed=%d skipped=%d\n", count($results), $success, $failed, $skipped);
    foreach ($results as $result) {
        $line = sprintf("router=%d tenant=%d status=%s sessions=%d", $result['routerId'], $result['tenantId'], $result['status'], $result['sessions']);
        if ($result['error'] !== null) {
            $line .= ' error=' . preg_replace('/\s+/', ' ', (string) $result['error']);
        }
        fwrite($result['status'] === 'failed' ? STDERR : STDOUT, $line . "\n");
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'PPPoE activity worker failed: ' . substr($e->getMessage(), 0, 1000) . "\n");
    exit(1);
} finally {
    foreach ($routerLocks as $handle) {
        if (is_resource($handle)) {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
    if (is_resource($globalLock)) {
        flock($globalLock, LOCK_UN);
        fclose($globalLock);
    }
}
