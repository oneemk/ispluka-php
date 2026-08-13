<?php

declare(strict_types=1);
namespace Ispluka\Core\Reseller;
use Ispluka\Core\Database\Database;
use RuntimeException;
final class ResellerService {
 public function __construct(private readonly Database $db) {}
 public function dashboard(int $tenantId,int $resellerId):array{$pdo=$this->db->pdo();$q=function(string $sql)use($pdo,$tenantId,$resellerId){$s=$pdo->prepare($sql);$s->execute([':t'=>$tenantId,':r'=>$resellerId]);return$s->fetchColumn();};return['customers'=>(int)$q('SELECT COUNT(*) FROM customers WHERE tenant_id=:t AND reseller_id=:r'),'active_services'=>(int)$q("SELECT COUNT(*) FROM customer_services cs JOIN customers c ON c.id=cs.customer_id WHERE cs.tenant_id=:t AND c.reseller_id=:r AND cs.status='active'"),'suspended_services'=>(int)$q("SELECT COUNT(*) FROM customer_services cs JOIN customers c ON c.id=cs.customer_id WHERE cs.tenant_id=:t AND c.reseller_id=:r AND cs.status='suspended'"),'outstanding'=>(int)$q("SELECT COALESCE(SUM(i.total-i.paid_amount),0) FROM invoices i JOIN customers c ON c.id=i.customer_id WHERE i.tenant_id=:t AND c.reseller_id=:r AND i.status IN ('issued','partial','overdue')"),'balance'=>(int)$q('SELECT balance FROM reseller_profiles WHERE tenant_id=:t AND id=:r')];}
 public function assignCustomer(int $tenantId,int $resellerId,int $customerId):void{$s=$this->db->pdo()->prepare('UPDATE customers SET reseller_id=:r,updated_at=CURRENT_TIMESTAMP WHERE tenant_id=:t AND id=:c');$s->execute([':t'=>$tenantId,':r'=>$resellerId,':c'=>$customerId]);if($s->rowCount()!==1)throw new RuntimeException('Customer not found in tenant.');}
 public function customers(int $tenantId,int $resellerId):array{$s=$this->db->pdo()->prepare('SELECT id,name,phone,email,status FROM customers WHERE tenant_id=:t AND reseller_id=:r ORDER BY id DESC');$s->execute([':t'=>$tenantId,':r'=>$resellerId]);return$s->fetchAll();}
}
