<?php

declare(strict_types=1);

use Ispluka\Core\Network\RouterOsApiClient;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

if ($argc < 4) {
    fwrite(STDERR, "Usage: php scripts/network/test-mikrotik-api.php HOST PORT USER [PASSWORD] [SSL]\n");
    exit(2);
}

$host = trim((string)$argv[1]);
$port = (int)$argv[2];
$user = (string)$argv[3];
$password = $argc >= 5 ? (string)$argv[4] : '';
$ssl = $argc >= 6 && in_array(strtolower((string)$argv[5]), ['1', 'true', 'yes', 'ssl'], true);

if ($host === '' || $port < 1 || $port > 65535 || $user === '') {
    fwrite(STDERR, "Invalid HOST/PORT/USER.\n");
    exit(2);
}

$source = @shell_exec('curl -4 -sS --max-time 5 https://api.ipify.org 2>/dev/null');
$source = trim((string)$source);
if ($source !== '') echo "Source public IPv4: {$source}\n";

echo "Target: {$host}:{$port}" . ($ssl ? " (API-SSL)" : " (API/TCP)") . "\n";

echo "TCP preflight: ";
$errno = 0;
$errstr = '';
$scheme = $ssl ? 'tls' : 'tcp';
$context = stream_context_create($ssl ? ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]] : []);
$socket = @stream_socket_client("{$scheme}://{$host}:{$port}", $errno, $errstr, 5.0, STREAM_CLIENT_CONNECT, $context);
if (!is_resource($socket)) {
    echo "FAILED\n";
    echo "errno={$errno}\n";
    echo "error=" . trim($errstr) . "\n";
    echo "This failure occurs before RouterOS authentication.\n";
    exit(10);
}
fclose($socket);
echo "OK\n";

$client = new RouterOsApiClient();
try {
    echo "RouterOS login: ";
    $client->connect([
        'host' => $host,
        'api_port' => $port,
        'username' => $user,
        'password' => $password,
        'api_ssl' => $ssl,
        'verify_ssl' => false,
    ]);
    echo "OK\n";

    echo "Identity: ";
    $rows = $client->command('/system/identity/print');
    echo (($rows[0]['name'] ?? '(unknown)')) . "\n";

    echo "Resource: ";
    $resource = $client->command('/system/resource/print');
    echo json_encode($resource[0] ?? [], JSON_UNESCAPED_SLASHES) . "\n";
    echo "RESULT: MikroTik API connection is working.\n";
    exit(0);
} catch (Throwable $e) {
    echo "FAILED\n";
    echo "error=" . $e->getMessage() . "\n";
    echo "RESULT: MikroTik API connection failed.\n";
    exit(11);
} finally {
    try { $client->disconnect(); } catch (Throwable $ignore) {}
}
