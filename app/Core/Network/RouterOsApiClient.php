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
        $verifySsl = (bool)($router['verify_ssl'] ?? false);

        if ($host === '' || $username === '') {
            throw new RuntimeException('Router connection settings are incomplete.');
        }
        if ($port < 1 || $port > 65535) {
            throw new RuntimeException('Invalid RouterOS API port: ' . $port);
        }

        // RouterOS API is plain TCP on the configured api port. API-SSL is
        // selected explicitly by using api_ssl_port, not by guessing from 8729.
        $ssl = !empty($router['api_ssl']) || (($router['transport'] ?? '') === 'ssl');
        $scheme = $ssl ? 'tls' : 'tcp';
        $context = stream_context_create($ssl ? ['ssl' => [
            'verify_peer' => $verifySsl,
            'verify_peer_name' => $verifySsl,
            'allow_self_signed' => !$verifySsl,
            'SNI_enabled' => true,
        ]] : []);

        $errno = 0;
        $errstr = '';
        $target = $scheme . '://' . $host . ':' . $port;
        $this->socket = @stream_socket_client(
            $target,
            $errno,
            $errstr,
            8.0,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!is_resource($this->socket)) {
            $detail = trim($errstr) !== '' ? ': ' . trim($errstr) : '';
            $hint = $errno === 111 || stripos($errstr, 'refused') !== false
                ? ' TCP connection was refused before RouterOS authentication. Verify that this IP:port is actually the MikroTik API service and that the hosting server IP is allowed to reach it.'
                : '';
            throw new RuntimeException('Unable to connect to MikroTik RouterOS API (' . $host . ':' . $port . ')' . $detail . '.' . $hint);
        }

        stream_set_timeout($this->socket, 8);
        $this->login($username, $password);
    }

    private function login(string $username, string $password): void
    {
        // MikroTik documents plaintext login for RouterOS >= 6.43 and the
        // challenge/MD5 flow for older releases. Send the modern form first.
        $reply = $this->commandAll('/login', ['name' => $username, 'password' => $password]);

        foreach ($reply as $sentence) {
            if (($sentence['!type'] ?? '') === '!trap' || ($sentence['!type'] ?? '') === '!fatal') {
                $this->disconnect();
                throw new RuntimeException('MikroTik authentication failed: ' . (string)($sentence['message'] ?? 'login rejected'));
            }
        }

        foreach ($reply as $sentence) {
            if (!isset($sentence['ret'])) continue;
            $challenge = hex2bin((string)$sentence['ret']);
            if ($challenge === false) throw new RuntimeException('Invalid MikroTik login challenge.');
            $response = '00' . md5("\x00" . $password . $challenge);
            $legacy = $this->commandAll('/login', ['name' => $username, 'response' => $response]);
            foreach ($legacy as $sentence2) {
                if (($sentence2['!type'] ?? '') === '!trap' || ($sentence2['!type'] ?? '') === '!fatal') {
                    $this->disconnect();
                    throw new RuntimeException('MikroTik authentication failed: ' . (string)($sentence2['message'] ?? 'login rejected'));
                }
            }
            return;
        }
    }

    public function command(string $command, array $arguments = []): array
    {
        $sentences = $this->commandAll($command, $arguments);
        return array_values(array_filter($sentences, static fn(array $s): bool => ($s['!type'] ?? '') === '!re'));
    }

    private function commandAll(string $command, array $arguments = []): array
    {
        if (!is_resource($this->socket)) throw new RuntimeException('MikroTik API connection is not open.');
        if ($command === '' || $command[0] !== '/') throw new RuntimeException('Invalid RouterOS API command.');

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
        return $this->readSentences();
    }

    public function disconnect(): void
    {
        if (is_resource($this->socket)) fclose($this->socket);
        $this->socket = null;
    }

    private function readSentences(): array
    {
        $sentences = [];
        while (true) {
            $sentence = [];
            while (true) {
                $word = $this->readWord();
                if ($word === '') break;
                if ($word[0] === '!') {
                    $sentence['!type'] = $word;
                } elseif ($word[0] === '=') {
                    $parts = explode('=', substr($word, 1), 2);
                    if (($parts[0] ?? '') !== '') $sentence[$parts[0]] = $parts[1] ?? '';
                }
            }
            if ($sentence === []) continue;
            $sentences[] = $sentence;
            $type = $sentence['!type'] ?? '';
            if ($type === '!done' || $type === '!fatal' || $type === '!empty') break;
        }

        foreach ($sentences as $sentence) {
            if (($sentence['!type'] ?? '') === '!trap') {
                throw new RuntimeException((string)($sentence['message'] ?? 'RouterOS command failed.'));
            }
            if (($sentence['!type'] ?? '') === '!fatal') {
                throw new RuntimeException((string)($sentence['message'] ?? 'RouterOS API connection terminated.'));
            }
        }
        return $sentences;
    }

    private function writeWord(string $word): void
    {
        $data = $this->encodeLength(strlen($word)) . $word;
        $offset = 0;
        $total = strlen($data);
        while ($offset < $total) {
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
            if ($chunk === false || $chunk === '') {
                $meta = stream_get_meta_data($this->socket);
                if (($meta['timed_out'] ?? false) === true) throw new RuntimeException('MikroTik API read timed out.');
                throw new RuntimeException('MikroTik API connection closed unexpectedly.');
            }
            $data .= $chunk;
        }
        return $data;
    }
}
