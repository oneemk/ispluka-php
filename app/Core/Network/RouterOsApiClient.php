<?php

declare(strict_types=1);

namespace Ispluka\Core\Network;

use RuntimeException;

final class RouterOsApiClient implements MikrotikClientInterface
{
    private $socket = null;

    public function connect(array $router): void
    {
        $host = trim((string)($router['host'] ?? ''));
        $port = (int)($router['api_port'] ?? 8728);
        $username = (string)($router['username'] ?? '');
        $password = (string)($router['password'] ?? '');
        $verifySsl = (bool)($router['verify_ssl'] ?? true);

        if ($host === '' || $username === '') throw new RuntimeException('Router connection settings are incomplete.');
        if ($port < 1 || $port > 65535) throw new RuntimeException('Invalid RouterOS API port.');

        $scheme = $port === 8729 ? 'tls' : 'tcp';
        $context = [];
        if ($scheme === 'tls') {
            $context['ssl'] = [
                'verify_peer' => $verifySsl,
                'verify_peer_name' => $verifySsl,
                'allow_self_signed' => !$verifySsl,
                'SNI_enabled' => true,
            ];
        }

        $errno = 0;
        $errstr = '';
        $this->socket = @stream_socket_client(
            $scheme . '://' . $host . ':' . $port,
            $errno,
            $errstr,
            8.0,
            STREAM_CLIENT_CONNECT,
            stream_context_create($context)
        );
        if (!is_resource($this->socket)) throw new RuntimeException('Unable to connect to MikroTik router.');

        stream_set_timeout($this->socket, 8);
        $reply = $this->command('/login', ['name' => $username, 'password' => $password]);
        foreach ($reply as $row) {
            if (isset($row['!trap']) || isset($row['!fatal'])) {
                $this->disconnect();
                throw new RuntimeException('MikroTik authentication failed.');
            }
        }
    }

    public function command(string $command, array $arguments = []): array
    {
        if (!is_resource($this->socket)) throw new RuntimeException('MikroTik connection is not open.');
        $words = [$command];
        foreach ($arguments as $key => $value) {
            if ($value === null) continue;
            $key = (string)$key;
            $words[] = (str_starts_with($key, '=') || str_starts_with($key, '?'))
                ? $key . '=' . (string)$value
                : '=' . $key . '=' . (string)$value;
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
        $sentences = [];
        while (true) {
            $sentence = [];
            while (true) {
                $word = $this->readWord();
                if ($word === '') break;
                if ($word[0] === '!') $sentence['!type'] = $word;
                elseif ($word[0] === '=') {
                    $parts = explode('=', substr($word, 1), 2);
                    if (($parts[0] ?? '') !== '') $sentence[$parts[0]] = $parts[1] ?? '';
                }
            }
            if ($sentence !== []) $sentences[] = $sentence;
            $type = $sentence['!type'] ?? '';
            if ($type === '!done' || $type === '!fatal') break;
        }

        foreach ($sentences as $sentence) {
            if (($sentence['!type'] ?? '') === '!trap') {
                throw new RuntimeException((string)($sentence['message'] ?? 'RouterOS command failed.'));
            }
        }
        return array_values(array_filter($sentences, static fn(array $s): bool => ($s['!type'] ?? '') === '!re'));
    }

    private function writeWord(string $word): void
    {
        $data = $this->encodeLength(strlen($word)) . $word;
        $offset = 0;
        while ($offset < strlen($data)) {
            $written = fwrite($this->socket, substr($data, $offset));
            if ($written === false || $written === 0) throw new RuntimeException('MikroTik API write failed.');
            $offset += $written;
        }
    }

    private function readWord(): string
    {
        $length = $this->readLength();
        return $length === 0 ? '' : $this->readBytes($length);
    }

    private function readLength(): int
    {
        $first = ord($this->readBytes(1));
        if (($first & 0x80) === 0) return $first;
        if (($first & 0xC0) === 0x80) return (($first & 0x3F) << 8) | ord($this->readBytes(1));
        if (($first & 0xE0) === 0xC0) return (($first & 0x1F) << 16) | (ord($this->readBytes(1)) << 8) | ord($this->readBytes(1));
        if (($first & 0xF0) === 0xE0) return (($first & 0x0F) << 24) | (ord($this->readBytes(1)) << 16) | (ord($this->readBytes(1)) << 8) | ord($this->readBytes(1));
        if ($first === 0xF0) return (int)unpack('N', $this->readBytes(4))[1];
        throw new RuntimeException('Invalid MikroTik API word length.');
    }

    private function encodeLength(int $length): string
    {
        if ($length < 0x80) return chr($length);
        if ($length < 0x4000) return pack('n', $length | 0x8000);
        if ($length < 0x200000) { $length |= 0xC00000; return chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF); }
        if ($length < 0x10000000) return pack('N', $length | 0xE0000000);
        return chr(0xF0) . pack('N', $length);
    }

    private function readBytes(int $length): string
    {
        $data = '';
        while (strlen($data) < $length) {
            $chunk = fread($this->socket, $length - strlen($data));
            if ($chunk === false || $chunk === '') throw new RuntimeException('MikroTik API connection closed or timed out.');
            $data .= $chunk;
        }
        return $data;
    }
}
