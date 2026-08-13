<?php

declare(strict_types=1);

namespace Ispluka\Core\Network;

use RuntimeException;

final class RouterOsClient
{
    private $socket = null;
    private bool $loggedIn = false;

    public function __construct(
        private readonly string $host,
        private readonly int $port = 8728,
        private readonly int $timeout = 5,
        private readonly bool $verifyTls = true,
    ) {}

    public function connect(string $username, string $password): void
    {
        $scheme = $this->port === 8729 ? 'tls' : 'tcp';
        $context = stream_context_create($scheme === 'tls' ? [
            'ssl' => [
                'verify_peer' => $this->verifyTls,
                'verify_peer_name' => $this->verifyTls,
                'allow_self_signed' => !$this->verifyTls,
            ],
        ] : []);
        $address = sprintf('%s://%s:%d', $scheme, $this->host, $this->port);
        $errno = 0;
        $errstr = '';
        $this->socket = @stream_socket_client($address, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $context);
        if (!is_resource($this->socket)) {
            throw new RuntimeException("Unable to connect to RouterOS API: {$errstr}");
        }
        stream_set_timeout($this->socket, $this->timeout);
        $this->writeSentence(['/login', '=name=' . $username, '=password=' . $password]);
        $reply = $this->readSentence();
        if (($reply['!trap']['=message'] ?? null) !== null) {
            $this->disconnect();
            throw new RuntimeException('RouterOS authentication failed.');
        }
        $this->loggedIn = true;
    }

    /** @return list<array<string,string>> */
    public function command(string $path, array $arguments = []): array
    {
        if (!$this->loggedIn) {
            throw new RuntimeException('RouterOS client is not authenticated.');
        }
        $words = [$path];
        foreach ($arguments as $key => $value) {
            if ($value === null) continue;
            $words[] = str_starts_with((string) $key, '=') ? (string) $key . '=' . (string) $value : '=' . $key . '=' . (string) $value;
        }
        $this->writeSentence($words);
        $rows = [];
        while (true) {
            $sentence = $this->readSentence();
            if (isset($sentence['!trap'])) {
                throw new RuntimeException($sentence['!trap']['=message'] ?? 'RouterOS command failed.');
            }
            if (isset($sentence['!done'])) break;
            if (isset($sentence['!re'])) $rows[] = $sentence['!re'];
        }
        return $rows;
    }

    public function disconnect(): void
    {
        if (is_resource($this->socket)) @fclose($this->socket);
        $this->socket = null;
        $this->loggedIn = false;
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    private function writeSentence(array $words): void
    {
        foreach ($words as $word) $this->writeWord((string) $word);
        $this->writeRaw("\0");
    }

    private function writeWord(string $word): void
    {
        $length = strlen($word);
        if ($length < 0x80) $prefix = chr($length);
        elseif ($length < 0x4000) $prefix = chr(($length >> 8) | 0x80) . chr($length & 0xff);
        elseif ($length < 0x200000) $prefix = chr(($length >> 16) | 0xc0) . chr(($length >> 8) & 0xff) . chr($length & 0xff);
        elseif ($length < 0x10000000) $prefix = chr(($length >> 24) | 0xe0) . chr(($length >> 16) & 0xff) . chr(($length >> 8) & 0xff) . chr($length & 0xff);
        else $prefix = "\xf0" . pack('N', $length);
        $this->writeRaw($prefix . $word);
    }

    private function writeRaw(string $data): void
    {
        $remaining = strlen($data);
        $offset = 0;
        while ($remaining > 0) {
            $written = @fwrite($this->socket, substr($data, $offset));
            if ($written === false || $written === 0) throw new RuntimeException('RouterOS API write failed.');
            $offset += $written;
            $remaining -= $written;
        }
    }

    /** @return array<string,array<string,string>> */
    private function readSentence(): array
    {
        $result = [];
        while (true) {
            $word = $this->readWord();
            if ($word === '') break;
            if ($word[0] === '!') {
                $result[$word] = [];
                continue;
            }
            if (str_starts_with($word, '=')) {
                $parts = explode('=', substr($word, 1), 2);
                if (count($parts) === 2 && !empty($result)) {
                    $keys = array_keys($result);
                    $last = end($keys);
                    $result[$last]['=' . $parts[0]] = $parts[1];
                }
            }
        }
        return $result;
    }

    private function readWord(): string
    {
        $first = ord($this->readRaw(1));
        if ($first === 0) return '';
        if (($first & 0x80) === 0) $length = $first;
        elseif (($first & 0xc0) === 0x80) $length = (($first & 0x3f) << 8) | ord($this->readRaw(1));
        elseif (($first & 0xe0) === 0xc0) $length = (($first & 0x1f) << 16) | (ord($this->readRaw(1)) << 8) | ord($this->readRaw(1));
        elseif (($first & 0xf0) === 0xe0) $length = (($first & 0x0f) << 24) | (ord($this->readRaw(1)) << 16) | (ord($this->readRaw(1)) << 8) | ord($this->readRaw(1));
        else {
            $bytes = unpack('N', $this->readRaw(4));
            $length = $bytes[1];
        }
        return $this->readRaw($length);
    }

    private function readRaw(int $length): string
    {
        $data = '';
        while (strlen($data) < $length) {
            $chunk = @fread($this->socket, $length - strlen($data));
            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('RouterOS API read timeout or connection closed.');
            }
            $data .= $chunk;
        }
        return $data;
    }
}
