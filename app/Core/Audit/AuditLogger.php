<?php

declare(strict_types=1);
namespace Ispluka\Core\Audit;
use Ispluka\Core\Database\Database;
final class AuditLogger {
 public function __construct(private readonly Database $db) {}
 public function log(int $tenantId, ?int $userId, string $action, string $entity, ?int $entityId=null, array $meta=[]): void {
  $s=$this->db->pdo()->prepare('INSERT INTO audit_logs (tenant_id,user_id,action,entity,entity_id,metadata) VALUES (:t,:u,:a,:e,:i,CAST(:m AS jsonb))');
  $s->execute([':t'=>$tenantId,':u'=>$userId,':a'=>$action,':e'=>$entity,':i'=>$entityId,':m'=>json_encode($meta,JSON_THROW_ON_ERROR)]);
 }
}
