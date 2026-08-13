<?php

declare(strict_types=1);
namespace Ispluka\Core\Auth;
use Ispluka\Core\Database\Database;
final class LoginThrottle {
 public function __construct(private readonly Database $db) {}
 public function allowed(string $key,int $maxAttempts=5,int $windowMinutes=15):bool {
  $max=max(1,$maxAttempts); $window=max(1,$windowMinutes); $pdo=$this->db->pdo();
  $pdo->exec('CREATE TABLE IF NOT EXISTS login_attempts (attempt_key VARCHAR(255) PRIMARY KEY, window_started_at TIMESTAMPTZ NOT NULL, attempts INTEGER NOT NULL)');
  $s=$pdo->prepare("INSERT INTO login_attempts(attempt_key,window_started_at,attempts) VALUES(:k,CURRENT_TIMESTAMP,1) ON CONFLICT(attempt_key) DO UPDATE SET attempts=CASE WHEN login_attempts.window_started_at <= CURRENT_TIMESTAMP - (:w || ' minutes')::interval THEN 1 ELSE login_attempts.attempts+1 END, window_started_at=CASE WHEN login_attempts.window_started_at <= CURRENT_TIMESTAMP - (:w || ' minutes')::interval THEN CURRENT_TIMESTAMP ELSE login_attempts.window_started_at END RETURNING attempts");
  $s->execute([':k'=>$key,':w'=>$window]); return (int)$s->fetchColumn() <= $max;
 }
}
