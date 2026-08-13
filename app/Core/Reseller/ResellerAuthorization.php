<?php

declare(strict_types=1);
namespace Ispluka\Core\Reseller;
use Ispluka\Core\Database\Database;
use RuntimeException;
final class ResellerAuthorization {
 public function __construct(private readonly Database $db) {}
 public function assertCustomer(int $tenantId,int $resellerId,int $customerId):void{$s=$this->db->pdo()->prepare('SELECT 1 FROM customers WHERE tenant_id=:t AND reseller_id=:r AND id=:c');$s->execute([':t'=>$tenantId,':r'=>$resellerId,':c'=>$customerId]);if(!$s->fetchColumn())throw new RuntimeException('Customer is not assigned to this reseller.');}
 public function assertActive(int $tenantId,int $resellerId):void{$s=$this->db->pdo()->prepare('SELECT 1 FROM reseller_profiles WHERE tenant_id=:t AND id=:r AND active');$s->execute([':t'=>$tenantId,':r'=>$resellerId]);if(!$s->fetchColumn())throw new RuntimeException('Reseller account is inactive.');}
}
