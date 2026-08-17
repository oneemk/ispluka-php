<?php

declare(strict_types=1);

namespace Ispluka\Core\Network;

use phpseclib3\Net\SSH2;
use RuntimeException;

final class RouterOsSshClient implements MikrotikClientInterface
{
    private ?SSH2 $ssh = null;
    private array $selectors = [];

    public function connect(array $router): void
    {
        $this->disconnect();
        $host = trim((string)($router['host'] ?? ''));
        $port = (int)($router['ssh_port'] ?? 22);
        $username = (string)($router['username'] ?? '');
        $password = (string)($router['password'] ?? '');
        $timeout = max(3, min(30, (int)($router['connection_timeout'] ?? 8)));

        if ($host === '' || $username === '') {
            throw new RuntimeException('Router SSH connection settings are incomplete.');
        }
        if ($port < 1 || $port > 65535) {
            throw new RuntimeException('Invalid RouterOS SSH port: ' . $port);
        }

        try {
            $this->ssh = new SSH2($host, $port, $timeout);
            $this->ssh->setTimeout($timeout);
            if (!$this->ssh->login($username, $password)) {
                throw new RuntimeException('MikroTik RouterOS SSH authentication failed for user "' . $username . '" at ' . $host . ':' . $port . '.');
            }
            $this->selectors = [];
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            $this->disconnect();
            if (stripos($message, 'connection refused') !== false || stripos($message, 'actively refused') !== false) {
                throw new RuntimeException('Unable to connect to MikroTik RouterOS SSH (' . $host . ':' . $port . '): Connection refused. RouterOS SSH connection could not be established on the configured SSH port.', 0, $e);
            }
            if ($e instanceof RuntimeException) {
                throw $e;
            }
            throw new RuntimeException('Unable to connect to MikroTik RouterOS SSH (' . $host . ':' . $port . '): ' . $message, 0, $e);
        }
    }

    public function command(string $command, array $arguments = []): array
    {
        if (!$this->ssh instanceof SSH2 || !$this->ssh->isConnected()) {
            throw new RuntimeException('MikroTik SSH connection is not open.');
        }
        if ($command === '' || $command[0] !== '/') {
            throw new RuntimeException('Invalid RouterOS command.');
        }

        $built = $this->buildCommand($command, $arguments);
        $output = $this->ssh->exec($built);
        if ($output === false) {
            throw new RuntimeException('MikroTik SSH command failed.');
        }

        $output = (string)$output;
        $this->assertNoRouterOsError($output);
        return $this->parseRows($output);
    }

    public function disconnect(): void
    {
        if ($this->ssh instanceof SSH2) {
            try {
                $this->ssh->disconnect();
            } catch (\Throwable $ignore) {
            }
        }
        $this->ssh = null;
        $this->selectors = [];
    }

    private function buildCommand(string $command, array $arguments): string
    {
        $parts = array_values(array_filter(
            explode('/', trim($command, '/')),
            static fn(string $part): bool => $part !== ''
        ));

        if ($parts === []) {
            throw new RuntimeException('Invalid RouterOS command.');
        }

        $action = strtolower((string)end($parts));
        $resource = implode(' ', array_slice($parts, 0, -1));
        $base = '/' . implode(' ', $parts);
        $query = null;

        foreach ($arguments as $key => $value) {
            if ($value === null) {
                continue;
            }
            $key = (string)$key;
            if (str_starts_with($key, '?')) {
                $field = ltrim($key, '?');
                if ($field !== '') {
                    $query = $field . '=' . $this->quote((string)$value);
                    $this->selectors[$resource] = $query;
                }
            }
        }

        if ($action === 'print') {
            // RouterOS SSH's normal table output is intended for humans and
            // cannot be parsed reliably when columns wrap. For active PPPoE
            // sessions, request property=value output explicitly. RouterOS
            // documents `detail` as the machine-readable property=value form
            // and `without-paging` prevents screen pagination.
            if ($resource === 'ppp active') {
                return $base . ' detail without-paging' . ($query !== null ? ' where ' . $query : '');
            }

            return $base . ($query !== null ? ' where ' . $query : '');
        }

        if (in_array($action, ['set', 'disable', 'enable', 'remove'], true)) {
            $selector = $this->selectors[$resource] ?? null;
            if ($selector === null && isset($arguments['.id'])) {
                $selector = '.id=' . $this->quote((string)$arguments['.id']);
            }
            if ($selector === null) {
                throw new RuntimeException('SSH command requires a selected RouterOS record.');
            }

            $line = $base . ' [find where ' . $selector . ']';
            foreach ($arguments as $key => $value) {
                $key = (string)$key;
                if ($value === null || $key === '.id' || $key === 'detail' || str_starts_with($key, '?')) {
                    continue;
                }
                $line .= ' ' . $key . '=' . $this->quote((string)$value);
            }
            return $line;
        }

        if ($action === 'add') {
            $line = $base;
            foreach ($arguments as $key => $value) {
                $key = (string)$key;
                if ($value === null || $key === '.id' || $key === 'detail' || str_starts_with($key, '?')) {
                    continue;
                }
                $line .= ' ' . $key . '=' . $this->quote((string)$value);
            }
            return $line;
        }

        return $base;
    }

    private function parseRows(string $output): array
    {
        $rows = [];
        $current = [];

        foreach (preg_split('/\R/', $output) ?: [] as $rawLine) {
            $line = trim($rawLine);

            // Blank lines separate RouterOS `print detail` records.
            if ($line === '') {
                if ($current !== []) {
                    $rows[] = $current;
                    $current = [];
                }
                continue;
            }

            // Ignore terminal decoration/prompt lines.
            if (
                str_starts_with($line, 'Flags:') ||
                str_starts_with($line, 'Columns:') ||
                str_starts_with($line, ';;;') ||
                str_starts_with($line, '[admin@') ||
                str_starts_with($line, '[user@')
            ) {
                continue;
            }

            $index = null;
            if (preg_match('/^(\*?\d+)(?:\s+[A-Za-z]+)?\s+(.+)$/', $line, $m)) {
                $index = $m[1];
                $line = $m[2];

                // A new numbered record always starts a new row, even if
                // RouterOS did not insert a blank line before it.
                if ($current !== []) {
                    $rows[] = $current;
                    $current = [];
                }
            }

            $fields = [];
            preg_match_all(
                '/([A-Za-z0-9_.-]+)=("(?:\\\\.|[^"])*"|\S+)/',
                $line,
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $match) {
                $fields[$match[1]] = $this->unquote($match[2]);
            }

            // RouterOS resource-style output can also appear as key: value.
            if ($fields === [] && preg_match('/^([A-Za-z0-9_.-]+):\s*(.*)$/', $line, $match)) {
                $fields[$match[1]] = trim($match[2]);
            }

            if ($fields !== []) {
                if ($index !== null) {
                    $fields['.id'] = $index;
                }
                $current = array_merge($current, $fields);
            }
        }

        if ($current !== []) {
            $rows[] = $current;
        }

        if ($rows !== []) {
            return $rows;
        }

        $fallback = [];
        if (preg_match('/\bname\s*[=:]\s*"?([^"\r\n]+)"?/i', $output, $m)) {
            $fallback['name'] = trim($m[1]);
        }

        return $fallback !== [] ? [$fallback] : [];
    }

    private function assertNoRouterOsError(string $output): void
    {
        foreach (preg_split('/\R/', $output) ?: [] as $line) {
            $line = trim($line);
            if (
                $line !== '' &&
                preg_match('/^(failure|input does not match|expected end of command|bad command|syntax error)/i', $line)
            ) {
                throw new RuntimeException('RouterOS SSH command failed: ' . $line);
            }
        }
    }

    private function quote(string $value): string
    {
        if (
            in_array(strtolower($value), ['yes', 'no'], true) ||
            preg_match('/^-?\d+(?:\.\d+)?$/', $value)
        ) {
            return $value;
        }

        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
    }

    private function unquote(string $value): string
    {
        if (strlen($value) >= 2 && $value[0] === '"' && $value[strlen($value) - 1] === '"') {
            return stripcslashes(substr($value, 1, -1));
        }
        return $value;
    }
}
