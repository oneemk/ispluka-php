<?php

declare(strict_types=1);
namespace Ispluka\Core\Auth;
use RuntimeException;
final class PasswordService {
 public function hash(string $password): string { if(strlen($password)<10) throw new RuntimeException('Password must be at least 10 characters.'); return password_hash($password,PASSWORD_DEFAULT); }
 public function verify(string $password,string $hash): bool { return password_verify($password,$hash); }
 public function needsRehash(string $hash): bool { return password_needs_rehash($hash,PASSWORD_DEFAULT); }
}
