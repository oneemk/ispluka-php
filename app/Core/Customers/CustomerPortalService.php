<?php

declare(strict_types=1);
namespace Ispluka\Core\Customers;
use Ispluka\Core\Database\Database;
final class CustomerPortalService {
 public function __construct(private readonly Database $db) {}
 public function overview(int $tenantId,int $customerId):array {
  $pdo=$this->db->pdo(); $s=$pdo->prepare('SELECT id,name,phone,email,status FROM customers WHERE tenant_id=:t AND id=:c');$s->execute([':t'=>$tenantId,':c'=>$customerId]);$customer=$s->fetch();
  if(!$customer)return [];
  $s=$pdo->prepare('SELECT cs.id,cs.connection_type,cs.username,cs.status,cs.next_billing_at,p.name package_name FROM customer_services cs LEFT JOIN packages p ON p.id=cs.package_id WHERE cs.tenant_id=:t AND cs.customer_id=:c ORDER BY cs.id DESC');$s->execute([':t'=>$tenantId,':c'=>$customerId]);$customer['services']=$s->fetchAll(); return $customer;
 }
}
