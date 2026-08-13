<?php

declare(strict_types=1);
namespace Ispluka\Core\Billing;
use Ispluka\Core\Automation\SuspensionPolicyService;
use Ispluka\Core\Database\Database;
final class PaymentSettlementService {
 public function __construct(private readonly Database $db,private readonly PaymentAllocator $allocator,private readonly SuspensionPolicyService $policy) {}
 public function settle(int $tenantId,int $paymentId,int $invoiceId,int $amount):void { $this->allocator->allocate($tenantId,$paymentId,$invoiceId,$amount);$s=$this->db->pdo()->prepare('SELECT customer_id FROM invoices WHERE tenant_id=:t AND id=:i');$s->execute([':t'=>$tenantId,':i'=>$invoiceId]);$customer=$s->fetchColumn();if($customer!==false)$this->policy->restoreForCustomer($tenantId,(int)$customer); }
}
