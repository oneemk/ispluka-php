<?php

declare(strict_types=1);

namespace Ispluka\Core\Network;

use RuntimeException;

final class RouterOsApiClient implements MikrotikClientInterface
{
    private $socket = null;
    private bool $connected = false;

    public function connect(array $router): void
    {
        $host = (string)($router['host'] ?? '');
        $port = (int)($router['api_port'] ?? 8728);
        $username = (string)($router['username'] ?? '');
        $password = (string)($router['password'] ?? '');

        if ($host === '' || $username === '') {
            throw new RuntimeException('MikroTik router credentials are incomplete.');
        }
        if (!function_exists('stream_socket_client')) {
            throw new RuntimeException('PHP stream sockets are unavailable on this hosting environment.');
        }

        $errno = 0;
        $errstr = '';
        $this->socket = @stream_socket_client(
            'tcp://' . $host . ':' . $port,
            $errno,
            $errstr,
            8,
            STREAM_CLIENT_CONNECT
        );
        if (!$this->socket) {
            throw new RuntimeException('Unable to connect to MikroTik: ' . ($errstr ?: 'connection failed'));
        }

        stream_set_timeout($this->socket, 8);
        $this->writeSentence(['/login', '=name=' . $username, '=password=' . $password]);
        $reply = $this->readSentenceSet();
        foreach ($reply as $sentence) {
            if (($sentence['!type'] ?? '') === '!trap') {
                throw new RuntimeException((string)($sentence['message'] ?? 'MikroTik authentication failed.'));
            }
        }
        $this->connected = true;
    }

    public function command(string $command, array $args = []): array
    {
        if (!$this->connected || !is_resource($this->socket)) {
            throw new RuntimeException('MikroTik client is not connected.');
        }

        $words = [$command];
        foreach ($args as $key => $value) {
            if ($value === null) continue;
            $words[] = '=' . ltrim((string)$key, '=') . '=' . (string)$value;
        }
        $this->writeSentence($words);
        $sentences = $this->readSentenceSet();
        $result = [];
        foreach ($sentences as $sentence) {
            if (($sentence['!type'] ?? '') === '!trap') {
                throw new RuntimeException((string)($sentence['message'] ?? 'RouterOS command failed.'));
            }
            if (($sentence['!type'] ?? '') === '!re') {
                $result[] = $sentence;
            }
        }
        return $result;
    }

    public function disconnect(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
        $this->socket = null;
        $this->connected = false;
    }

    private function writeSentence(array $words): void
    {
        foreach ($words as $word) {
            $this->writeWord((string)$word);
        }
        $this->writeWord('');
    }

    private function writeWord(string $word): void
    {
        $length = strlen($word);
        if ($length < 0x80) {
            $prefix = chr($length);
        } elseif ($length < 0x4000) {
            $length |= 0x8000;
            $prefix = chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length < 0x200000) {
            $length |= 0xC00000;
            $prefix = chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } else {
            $prefix = "\xF0" . pack('N', $length);
        }
        $this->writeAll($prefix . $word);
    }

    private function writeAll(string $data): void
    {
        $offset = 0;
        $length = strlen($data);
        while ($offset < $length) {
            $written = fwrite($this->socket, substr($data, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('Failed writing to MikroTik API socket.');
            }
            $offset += $written;
        }
    }

    private function readSentenceSet(): array
    {
        $sentences = [];
        while (true) {
            $sentence = [];
            while (true) {
                $word = $this->readWord();
                if ($word === '') break;
                if ($word[0] === '=') {
                    $eq = strpos($word, '=', 1);
                    if ($eq !== false) {
                        $sentence[substr($word, 1, $eq - 1)] = substr($word, $eq + 1);
                    }
                } elseif ($word[0] === '!') {
                    $sentence['!type'] = $word;
                }
            }
            if ($sentence === []) continue;
            $sentences[] = $sentence;
            if (($sentence['!type'] ?? '') === '!done' || ($sentence['!type'] ?? '') === '!fatal') break;
        }
        return $sentences;
    }

    private function readWord(): string
    {
        $first = ord($this->readBytes(1));
        if ($first < 0x80) {
            $length = $first;
        } elseif (($first & 0xC0) === 0x80) {
            $length = (($first & 0x3F) << 8) | ord($this->readBytes(1));
        } elseif (($first & 0xE0) === 0xC0) {
            $length = (($first & 0x1F) << 16) | (ord($this->readBytes(1)) << 8) | ord($this->readBytes(1));
        } elseif (($first & 0xF0) === 0xE0) {
            $length = ($first & 0x0F) << 24;
            $length |= ord($this->readBytes(1)) << 16;
            $length |= ord($this->readBytes(1)) << 8;
            $length |= ord($this->readBytes(1));
        } else {
            $length = unpack('N', $this->readBytes(4))[1];
        }
        return $length === 0 ? '' : $this->readBytes($length);
    }

    private function readBytes(int $length): string
    {
        $data = '';
        while (strlen($data) < $length) {
            $chunk = fread($this->socket, $length - strlen($data));
            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('MikroTik API socket closed or timed out.');
            }
            $data .= $chunk;
        }
        return $data;
    }
}
