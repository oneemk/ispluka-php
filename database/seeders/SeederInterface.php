<?php

declare(strict_types=1);

namespace Ispluka\Database\Seeders;

use PDO;

interface SeederInterface
{
    public function run(PDO $pdo): void;
}
