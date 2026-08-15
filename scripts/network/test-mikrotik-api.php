<?php

declare(strict_types=1);

if ($argc < 4) {
    fwrite(STDERR, "Usage: php scripts/network/test-mikrotik-api.php HOST PORT USER [PASSWORD]\n");
    exit(2);
}

$host = $argv[1];
$port = (int)$argv[2];
$user = $argv[3];
$password = $argv[4] ?? '';

$errno = 0;
$error = '';
$socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $error, 8.0, STREAM_CLIENT_CONNECT);
if (!is_resource($socket)) {
    fwrite(STDERR, "TCP CONNECT FAILED: {$host}:{$port} errno={$errno} {$error}\n");
    exit(1);
}

stream_set_timeout($socket, 8);

echo "TCP CONNECT OK: {$host}:{$port}\n";

function writeWord($socket, string $word): void {
    $len = strlen($word);
    if ($len < 0x80) $prefix = chr($len);
    elseif ($len < 0x4000) $prefix = pack('n', $len | 0x8000);
    elseif ($len < 0x200000) { $len |= 0xC00000; $prefix = chr(($len >> 16) & 255) . chr(($len >> 8) & 255) . chr($len & 255); }
    elseif ($len < 0x10000000) $prefix = pack('N', $len | 0xE0000000);
    else $prefix = chr(0xF0) . pack('N', $len);
    fwrite($socket, $prefix . $word);
}

function readBytes($socket, int $length): string {
    $data = '';
    while (strlen($data) < $length) {
        $chunk = fread($socket, $length - strlen($data));
        if ($chunk === false || $chunk === '') throw new RuntimeException('Connection closed while reading.');
        $data .= $chunk;
    }
    return $data;
}

function readLength($socket): int {
    $first = ord(readBytes($socket, 1));
    if (($first & 0x80) === 0) return $first;
    if (($first & 0xC0) === 0x80) return (($first & 0x3F) << 8) | ord(readBytes($socket, 1));
    if (($first & 0xE0) === 0xC0) return (($first & 0x1F) << 16) | (ord(readBytes($socket, 1)) << 8) | ord(readBytes($socket, 1));
    if (($first & 0xF0) === 0xE0) return (($first & 0x0F) << 24) | (ord(readBytes($socket, 1)) << 16) | (ord(readBytes($socket, 1)) << 8) | ord(readBytes($socket, 1));
    if ($first === 0xF0) return (int)unpack('N', readBytes($socket, 4))[1];
    throw new RuntimeException('Invalid RouterOS word length.');
}

function readWord($socket): string {
    $length = readLength($socket);
    return $length === 0 ? '' : readBytes($socket, $length);
}

writeWord($socket, '/login');
writeWord($socket, '=name=' . $user);
writeWord($socket, '=password=' . $password);
writeWord($socket, '');

$reply = [];
while (true) {
    $word = readWord($socket);
    if ($word === '') break;
    $reply[] = $word;
}

echo "LOGIN REPLY:\n";
foreach ($reply as $word) {
    if (stripos($word, '=password=') === 0) $word = '=password=***';
    echo "  {$word}\n";
}

if (in_array('!trap', $reply, true) || in_array('!fatal', $reply, true)) exit(1);

fclose($socket);
echo "ROUTEROS API HANDSHAKE OK\n";
