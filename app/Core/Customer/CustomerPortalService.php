<?php

declare(strict_types=1);
namespace Ispluka\Core\Customer;
use Ispluka\Core\Database\Database;
use RuntimeException;
final class CustomerPortalService {
 public function __construct(private readonly Database $db) {}
 private function customer(int $tenantId,int $customerId):array{$s=$this->db->pdo()->prepare('SELECT id,name,phone,email,status FROM customers WHERE tenant_id=:t AND id=:c');$s->execute([':t'=>$tenantId,':c'=>$customerId]);$r=$s->fetch();if(!$r)throw new RuntimeException('Customer not found.');return$r;}
 public function dashboard(int $tenantId,int $customerId):array{$this->customer($tenantId,$customerId);$pdo=$this->db->pdo();$q=function(string $sql)use($pdo,$tenantId,$customerId){$s=$pdo->prepare($sql);$s->execute([':t'=>$tenantId,':c'=>$customerId]);return$s->fetchColumn();};return['customer'=>$this->customer($tenantId,$customerId),'services'=>(int)$q('SELECT COUNT(*) FROM customer_services WHERE tenant_id=:t AND customer_id=:c'),'active_services'=>(int)$q("SELECT COUNT(*) FROM customer_services WHERE tenant_id=:t AND customer_id=:c AND status='active'"),'suspended_services'=>(int)$q("SELECT COUNT(*) FROM customer_services WHERE tenant_id=:t AND customer_id=:c AND status='suspended'"),'outstanding'=>(int)$q("SELECT COALESCE(SUM(total-paid_amount),0) FROM invoices WHERE tenant_id=:t AND customer_id=:c AND status IN ('issued','partial','overdue')")];}
 public function services(int $tenantId,int $customerId):array{$this->customer($tenantId,$customerId);$s=$this->db->pdo()->prepare('SELECT cs.id,cs.status,cs.next_billing_at,cs.suspended_at,cs.suspended_reason,p.name package_name,p.price FROM customer_services cs LEFT JOIN packages p ON p.id=cs.package_id AND p.tenant_id=cs.tenant_id WHERE cs.tenant_id=:t AND cs.customer_id=:c ORDER BY cs.id DESC');$s->execute([':t'=>$tenantId,':c'=>$customerId]);return$s->fetchAll();}
 public function invoices(int $tenantId,int $customerId):array{$this->customer($tenantId,$customerId);$s=$this->db->pdo()->prepare('SELECT id,invoice_no,total,paid_amount,status,due_date,created_at FROM invoices WHERE tenant_id=:t AND customer_id=:c ORDER BY id DESC LIMIT 100');$s->execute([':t'=>$tenantId,':c'=>$customerId]);return$s->fetchAll();}
 public function payments(int $tenantId,int $customerId):array{$this->customer($tenantId,$customerId);$s=$this->db->pdo()->prepare('SELECT p.id,p.amount,p.status,p.paid_at,p.method,p.reference FROM payments p WHERE p.tenant_id=:t AND p.customer_id=:c ORDER BY p.id DESC LIMIT 100');$s->execute([':t'=>$tenantId,':c'=>$customerId]);return$s->fetchAll();}
}
