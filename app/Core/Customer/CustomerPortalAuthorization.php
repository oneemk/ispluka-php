<?php

declare(strict_types=1);
namespace Ispluka\Core\Customer;
use Ispluka\Core\Database\Database;
use RuntimeException;
final class CustomerPortalAuthorization {
 public function __construct(private readonly Database $db) {}
 public function assertAccount(int $tenantId,int $customerId,int $userId):void{$s=$this->db->pdo()->prepare("SELECT 1 FROM customers c JOIN users u ON u.id=:u AND u.tenant_id=c.tenant_id WHERE c.tenant_id=:t AND c.id=:c AND (c.user_id=:u OR u.id=c.user_id)");$s->execute([':t'=>$tenantId,':c'=>$customerId,':u'=>$userId]);if(!$s->fetchColumn())throw new RuntimeException('Customer portal access denied.');}
}
