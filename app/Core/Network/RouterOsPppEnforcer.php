<?php

declare(strict_types=1);

namespace Ispluka\Core\Network;

use RuntimeException;

final class RouterOsPppEnforcer
{
    /** @var callable(string, array): mixed */
    private $command;

    /** @param callable(string,array):mixed $command */
    public function __construct(callable $command)
    {
        $this->command = $command;
    }

    public function enable(string $id): void
    {
        $this->run('/ppp/secret/set', ['.id' => $id, 'disabled' => 'no']);
    }

    public function disable(string $id): void
    {
        $this->run('/ppp/secret/set', ['.id' => $id, 'disabled' => 'yes']);
    }

    public function setProfile(string $id, string $profile): void
    {
        $this->run('/ppp/secret/set', ['.id' => $id, 'profile' => $profile]);
    }

    /** Resolve the RouterOS internal .id from an exact PPP secret username. */
    public function resolveId(string $username): string
    {
        $rows = ($this->command)('/ppp/secret/print', [
            '?name' => $username,
            '=.proplist' => '.id,name',
        ]);

        if (!is_array($rows) || count($rows) !== 1 || empty($rows[0]['.id'])) {
            throw new RuntimeException('MikroTik PPPoE secret could not be uniquely resolved.');
        }

        return (string) $rows[0]['.id'];
    }

    private function run(string $path, array $args): void
    {
        try {
            $result = ($this->command)($path, $args);

            if ($result === false) {
                throw new RuntimeException('RouterOS command failed.');
            }
        } catch (\Throwable $e) {
            throw new RuntimeException('MikroTik PPPoE enforcement failed: '.$e->getMessage(), 0, $e);
        }
    }
}
