<?php

declare(strict_types=1);

namespace Ispluka\Database\Seeders;

use PDO;
use RuntimeException;

final class SeederRunner
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @param list<SeederInterface> $seeders
     * @return list<string>
     */
    public function run(array $seeders): array
    {
        $executed = [];

        foreach ($seeders as $seeder) {
            if (!$seeder instanceof SeederInterface) {
                throw new RuntimeException('Invalid seeder: ' . get_debug_type($seeder));
            }

            $seeder->run($this->pdo);
            $executed[] = $seeder::class;
        }

        return $executed;
    }
}
