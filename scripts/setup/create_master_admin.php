<?php

declare(strict_types=1);

use Ispluka\Core\Auth\Password;
use Ispluka\Core\Auth\RoleManager;
use Ispluka\Core\Database\Database;
use Ispluka\Core\Environment;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$root = dirname(__DIR__, 2);
$env = $root . '/.env';
if (is_file($env)) {
    Environment::load($env);
}

$required = ['MASTER_ADMIN_NAME', 'MASTER_ADMIN_EMAIL', 'MASTER_ADMIN_PASSWORD'];
foreach ($required as $key) {
    if (trim((string) getenv($key)) === '') {
        fwrite(STDERR, "Missing environment variable: {$key}\n");
        exit(1);
    }
}

$config = require $root . '/config/database.php';
$database = new Database($config);
$pdo = $database->pdo();

$email = strtolower(trim((string) getenv('MASTER_ADMIN_EMAIL')));
$password = (string) getenv('MASTER_ADMIN_PASSWORD');
$name = trim((string) getenv('MASTER_ADMIN_NAME'));

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 16) {
    fwrite(STDERR, "Invalid master admin credentials. Password must be at least 16 characters.\n");
    exit(1);
}

$existing = $pdo->prepare(
    'SELECT id FROM users WHERE tenant_id IS NULL AND lower(email) = lower(:email) AND deleted_at IS NULL LIMIT 1'
);
$existing->execute(['email' => $email]);
if ($existing->fetchColumn() !== false) {
    fwrite(STDERR, "A master admin with this email already exists.\n");
    exit(1);
}

$user = $pdo->prepare(
    'INSERT INTO users (tenant_id, name, email, password_hash, status, password_changed_at)
     VALUES (NULL, :name, :email, :password_hash, \'active\', CURRENT_TIMESTAMP)
     RETURNING id'
);
$user->execute([
    'name' => $name,
    'email' => $email,
    'password_hash' => Password::hash($password),
]);
$userId = (int) $user->fetchColumn();

if ($userId <= 0) {
    fwrite(STDERR, "Unable to create master admin.\n");
    exit(1);
}

(new RoleManager($database))->assign($userId, 'master_admin', null);

fwrite(STDOUT, "Master Admin created successfully. User ID: {$userId}\n");
