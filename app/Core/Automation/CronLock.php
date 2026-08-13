<?php

declare(strict_types=1);
namespace Ispluka\Core\Automation;
use Ispluka\Core\Database\Database;
final class CronLock {
 public function __construct(private readonly Database $db) {}
 public function acquire(string $name,int $ttl=300):bool { $s=$this->db->pdo()->prepare("SELECT pg_try_advisory_lock(hashtext(:n))");$s->execute([':n'=>$name]);return(bool)$s->fetchColumn(); }
 public function release(string $name):void { $s=$this->db->pdo()->prepare('SELECT pg_advisory_unlock(hashtext(:n))');$s->execute([':n'=>$name]); }
}
