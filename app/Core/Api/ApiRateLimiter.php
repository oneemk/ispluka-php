<?php

declare(strict_types=1);
namespace Ispluka\Core\Api;
use Ispluka\Core\Database\Database;
final class ApiRateLimiter {
 public function __construct(private readonly Database $db) {}
 public function allow(string $key,int $limit=120,int $windowSeconds=60): bool {
  $limit=max(1,min($limit,1000)); $window=max(1,$windowSeconds); $pdo=$this->db->pdo();
  $pdo->exec('CREATE TABLE IF NOT EXISTS api_rate_limits (rate_key VARCHAR(255) PRIMARY KEY, window_started_at TIMESTAMPTZ NOT NULL, request_count INTEGER NOT NULL)');
  $s=$pdo->prepare("INSERT INTO api_rate_limits(rate_key,window_started_at,request_count) VALUES(:k,CURRENT_TIMESTAMP,1) ON CONFLICT(rate_key) DO UPDATE SET request_count=CASE WHEN api_rate_limits.window_started_at <= CURRENT_TIMESTAMP - (:window || ' seconds')::interval THEN 1 ELSE api_rate_limits.request_count+1 END, window_started_at=CASE WHEN api_rate_limits.window_started_at <= CURRENT_TIMESTAMP - (:window || ' seconds')::interval THEN CURRENT_TIMESTAMP ELSE api_rate_limits.window_started_at END RETURNING request_count");
  $s->execute([':k'=>$key,':window'=>$window]); return (int)$s->fetchColumn() <= $limit;
 }
}
