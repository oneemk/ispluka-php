<?php

declare(strict_types=1);

namespace Ispluka\Core\Mikrotik;

use RuntimeException;

final class RouterOsClient
{
    /** @return array<int, array<string, string>> */
    public function command(string $host, int $port, string $username, string $password, string $command, array $attributes = [], int $timeout = 5): array
    {
        if ($host === '' || $username === '' || $command === '' || $timeout < 1 || $timeout > 30) {
            throw new RuntimeException('Invalid RouterOS connection parameters.');
        }
        $socket = @fsockopen($host, $port, $errno, $error, $timeout);
        if (!is_resource($socket)) {
            throw new RuntimeException('Unable to connect to MikroTik router.');
        }
        stream_set_timeout($socket, $timeout);
        try {
            $this->writeSentence($socket, ['/login', '=name=' . $username, '=password=' . $password]);
            $login = $this->readSentence($socket);
            if (!$login['done']) {
                throw new RuntimeException('MikroTik authentication failed.');
            }
            $sentence = [$command];
            foreach ($attributes as $key => $value) {
                $sentence[] = '=' . ltrim((string) $key, '=') . '=' . (string) $value;
            }
            $this->writeSentence($socket, $sentence);
            return $this->readResults($socket);
        } finally {
            fclose($socket);
        }
    }

    private function writeSentence($socket, array $words): void
    {
        foreach ($words as $word) {
            $this->writeWord($socket, $word);
        }
        $this->writeWord($socket, '');
    }

    private function writeWord($socket, string $word): void
    {
        $length = strlen($word);
        if ($length < 0x80) $prefix = chr($length);
        elseif ($length < 0x4000) $prefix = chr(($length >> 8) | 0x80) . chr($length & 0xff);
        elseif ($length < 0x200000) $prefix = chr(($length >> 16) | 0xc0) . chr(($length >> 8) & 0xff) . chr($length & 0xff);
        elseif ($length < 0x10000000) $prefix = chr(($length >> 24) | 0xe0) . chr(($length >> 16) & 0xff) . chr(($length >> 8) & 0xff) . chr($length & 0xff);
        else $prefix = "\xf0" . pack('N', $length);
        if (fwrite($socket, $prefix . $word) === false) throw new RuntimeException('Router write failed.');
    }

    private function readWord($socket): ?string
    {
        $first = fread($socket, 1);
        if ($first === '' || $first === false) return null;
        $c = ord($first);
        if ($c === 0) return '';
        if (($c & 0x80) === 0) $length = $c;
        elseif (($c & 0xc0) === 0x80) $length = (($c & 0x3f) << 8) | ord(fread($socket, 1));
        elseif (($c & 0xe0) === 0xc0) $length = (($c & 0x1f) << 16) | (ord(fread($socket, 1)) << 8) | ord(fread($socket, 1));
        elseif (($c & 0xf0) === 0xe0) $length = (($c & 0x0f) << 24) | (ord(fread($socket, 1)) << 16) | (ord(fread($socket, 1)) << 8) | ord(fread($socket, 1));
        else { fread($socket, 4); throw new RuntimeException('Unsupported RouterOS API word length.'); }
        $data = '';
        while (strlen($data) < $length) { $chunk = fread($socket, $length - strlen($data)); if ($chunk === false || $chunk === '') throw new RuntimeException('Router read failed.'); $data .= $chunk; }
        return $data;
    }

    private function readSentence($socket): array
    {
        $words = [];
        while (($word = $this->readWord($socket)) !== null && $word !== '') $words[] = $word;
        $done = false;
        foreach ($words as $word) if ($word === '!done') $done = true;
        return ['done' => $done];
    }

    private function readResults($socket): array
    {
        $rows = []; $row = [];
        while (true) {
            $word = $this->readWord($socket);
            if ($word === null) break;
            if ($word === '') { if ($row !== []) { $rows[] = $row; $row = []; } continue; }
            if ($word === '!done') { if ($row !== []) $rows[] = $row; break; }
            if ($word === '!trap' || $word === '!fatal') throw new RuntimeException('MikroTik command failed.');
            if (str_starts_with($word, '=')) { $parts = explode('=', substr($word, 1), 2); $row[$parts[0]] = $parts[1] ?? ''; }
        }
        return $rows;
    }
}
