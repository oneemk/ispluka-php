<?php

declare(strict_types=1);

use Ispluka\Core\Database\Database;
use Ispluka\Core\Environment;
use Ispluka\Database\Migrations\MigrationInterface;
use Ispluka\Database\Migrations\MigrationRunner;
use PDO;

$root = dirname(__DIR__, 2);
require_once $root . '/vendor/autoload.php';

$environmentFile = $root . '/.env';
if (is_file($environmentFile)) {
    Environment::load($environmentFile);
}

$database = new Database(require $root . '/config/database.php');
$runner = new MigrationRunner($database->pdo());
$migrations = [];
$directory = $root . '/database/migrations';
$files = glob($directory . '/*.{php,sql}', GLOB_BRACE) ?: [];
sort($files, SORT_STRING);

foreach ($files as $file) {
    $name = basename($file);
    if ($name === 'MigrationInterface.php' || $name === 'MigrationRunner.php') {
        continue;
    }

    if (str_ends_with($name, '.php')) {
        $migration = require $file;
        if (!is_object($migration) || !method_exists($migration, 'up')) {
            throw new RuntimeException('Invalid PHP migration: ' . $name);
        }
        $migrations[$name] = $migration;
        continue;
    }

    $sql = trim((string) file_get_contents($file));
    if ($sql === '') {
        continue;
    }

    $migrations[$name] = new class($sql) implements MigrationInterface {
        public function __construct(private readonly string $sql) {}
        public function up(PDO $pdo): void { $pdo->exec($this->sql); }
        public function down(PDO $pdo): void {}
    };
}

$executed = $runner->migrate($migrations);
if ($executed === []) {
    echo "No pending migrations.\n";
    exit(0);
}

echo "Applied migrations:\n";
foreach ($executed as $name) {
    echo "- {$name}\n";
}
