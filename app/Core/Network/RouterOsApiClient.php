<?php

declare(strict_types=1);

namespace Ispluka\Core\Network;

use RuntimeException;

/** Minimal native RouterOS API client; no Composer dependency required. */
final class RouterOsApiClient implements MikrotikClientInterface
{
    private $socket = null;

    public function connect(array $router): void
    {
        $host = trim((string)($router['host'] ?? ''));
        $port = (int)($router['api_port'] ?? 8728);
        $user = (string)($router['username'] ?? '');
        $password = (string)($router['password'] ?? '');
        if ($host === '' || $user === '') throw new RuntimeException('Router connection settings are incomplete.');
        if ($port < 1 || $port > 65535) throw new RuntimeException('Invalid RouterOS API port.');
        $errno = 0; $errstr = '';
        $timeout = 8.0;
        $this->socket = @stream_socket_client('tcp://' . $host . ':' . $port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
        if (!is_resource($this->socket)) throw new RuntimeException('Unable to connect to MikroTik router.');
        stream_set_timeout($this->socket, 8);
        $reply = $this->command('/login', ['name' => $user, 'password' => $password]);
        $failed = false;
        foreach ($reply as $row) if (($row['!trap'] ?? '') !== '' || ($row['!fatal'] ?? '') !== '') $failed = true;
        if ($failed) { $this->disconnect(); throw new RuntimeException('MikroTik authentication failed.'); }
    }

    public function command(string $command, array $arguments = []): array
    {
        if (!is_resource($this->socket)) throw new RuntimeException('MikroTik connection is not open.');
        $words = [$command];
        foreach ($arguments as $key => $value) {
            if ($value === null) continue;
            $words[] = str_starts_with((string)$key, '=') || str_starts_with((string)$key, '?')
                ? (string)$key . '=' . (string)$value
                : '=' . (string)$key . '=' . (string)$value;
        }
        foreach ($words as $word) $this->writeWord($word);
        $this->writeWord('');
        return $this->readSentence();
    }

    public function disconnect(): void
    {
        if (is_resource($this->socket)) fclose($this->socket);
        $this->socket = null;
    }

    private function readSentence(): array
    {
        $rows = [];
        while (true) {
            $word = $this->readWord();
            if ($word === '') break;
            if ($word[0] === '!') { $rows[] = [$word => $word]; if (in_array($word, ['!done','!trap','!fatal'], true)) break; continue; }
            if (str_starts_with($word, '=')) {
                $parts = explode('=', substr($word, 1), 2); $key = $parts[0] ?? ''; $value = $parts[1] ?? '';
                if ($key !== '') $rows[count($rows) - 1][$key] = $value;
            }
        }
        return $rows;
    }

    private function writeWord(string $word): void
    {
        $data = $this->encodeLength(strlen($word)) . $word;
        $written = fwrite($this->socket, $data);
        if ($written === false || $written !== strlen($data)) throw new RuntimeException('MikroTik API write failed.');
    }

    private function readWord(): string
    {
        $length = $this->readLength();
        if ($length === 0) return '';
        $data = '';
        while (strlen($data) < $length) {
            $chunk = fread($this->socket, $length - strlen($data));
            if ($chunk === false || $chunk === '') throw new RuntimeException('MikroTik API connection closed.');
            $data .= $chunk;
        }
        return $data;
    }

    private function readLength(): int
    {
        $first = ord($this->readBytes(1));
        if (($first & 0x80) === 0) return $first;
        if (($first & 0xC0) === 0x80) return (($first & 0x3F) << 8) | ord($this->readBytes(1));
        if (($first & 0xE0) === 0xC0) return (($first & 0x1F) << 16) | (ord($this->readBytes(1)) << 8) | ord($this->readBytes(1));
        if (($first & 0xF0) === 0xE0) return (($first & 0x0F) << 24) | (ord($this->readBytes(1)) << 16) | (ord($this->readBytes(1)) << 8) | ord($this->readBytes(1));
        if ($first === 0xF0) { $b = unpack('N', $this->readBytes(4)); return (int)$b[1]; }
        throw new RuntimeException('Invalid MikroTik API word length.');
    }

    private function encodeLength(int $length): string
    {
        if ($length < 0x80) return chr($length);
        if ($length < 0x4000) return pack('n', $length | 0x8000);
        if ($length < 0x200000) { $length |= 0xC00000; return chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF); }
        if ($length < 0x10000000) { $length |= 0xE0000000; return pack('N', $length); }
        return chr(0xF0) . pack('N', $length);
    }

    private function readBytes(int $length): string
    {
        $data = '';
        while (strlen($data) < $length) {
            $chunk = fread($this->socket, $length - strlen($data));
            if ($chunk === false || $chunk === '') throw new RuntimeException('MikroTik API read failed.');
            $data .= $chunk;
        }
        return $data;
    }
}
