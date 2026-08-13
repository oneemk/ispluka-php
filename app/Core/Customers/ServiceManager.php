<?php

declare(strict_types=1);
namespace Ispluka\Core\Customers;
use Ispluka\Core\Database\Database;
use RuntimeException;
final class ServiceManager {
 public function __construct(private readonly Database $db) {}
 public function create(int $tenantId,int $customerId,array $data):int {
  $type=strtolower(trim((string)($data['connection_type']??'pppoe'))); if(!in_array($type,['pppoe','hotspot'],true)) throw new RuntimeException('Unsupported connection type.');
  $s=$this->db->pdo()->prepare('INSERT INTO customer_services (tenant_id,customer_id,package_id,router_id,connection_type,username,password_encrypted,billing_day,next_billing_at) VALUES (:t,:c,:p,:r,:ct,:u,:pw,:bd,CURRENT_TIMESTAMP) RETURNING id');
  $s->execute([':t'=>$tenantId,':c'=>$customerId,':p'=>$data['package_id']??null,':r'=>$data['router_id']??null,':ct'=>$type,':u'=>trim((string)($data['username']??''))?:null,':pw'=>$data['password_encrypted']??null,':bd'=>min(max((int)($data['billing_day']??1),1),28)]); return (int)$s->fetchColumn();
 }
 public function suspend(int $tenantId,int $serviceId,string $reason):void { $s=$this->db->pdo()->prepare("UPDATE customer_services SET status='suspended',suspended_at=CURRENT_TIMESTAMP,suspended_reason=:r,updated_at=CURRENT_TIMESTAMP WHERE tenant_id=:t AND id=:id");$s->execute([':t'=>$tenantId,':id'=>$serviceId,':r'=>trim($reason)]); }
 public function restore(int $tenantId,int $serviceId):void { $s=$this->db->pdo()->prepare("UPDATE customer_services SET status='active',suspended_at=NULL,suspended_reason=NULL,updated_at=CURRENT_TIMESTAMP WHERE tenant_id=:t AND id=:id");$s->execute([':t'=>$tenantId,':id'=>$serviceId]); }
}
