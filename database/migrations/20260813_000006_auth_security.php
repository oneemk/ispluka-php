<?php

declare(strict_types=1);
use PDO;
return new class {
 public function up(PDO $pdo):void{
  $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (id BIGSERIAL PRIMARY KEY,user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,token_hash CHAR(64) NOT NULL UNIQUE,expires_at TIMESTAMPTZ NOT NULL,used_at TIMESTAMPTZ NULL,created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP)");
  $pdo->exec('CREATE INDEX IF NOT EXISTS idx_password_reset_user_expires ON password_reset_tokens(user_id,expires_at)');
 }
 public function down(PDO $pdo):void{$pdo->exec('DROP TABLE IF EXISTS password_reset_tokens');}
};
