<?php

declare(strict_types=1);
namespace Ispluka\Core\Reseller;
use Ispluka\Core\Billing\PaymentSettlementService;
use Ispluka\Core\Database\Database;
final class ResellerPaymentService {
 public function __construct(private readonly Database $db,private readonly ResellerAuthorization $auth,private readonly PaymentSettlementService $settlement,private readonly CommissionService $commission) {}
 public function collect(int $tenantId,int $resellerId,int $customerId,int $paymentId,int $invoiceId,int $amount):?int{$this->auth->assertActive($tenantId,$resellerId);$this->auth->assertCustomer($tenantId,$resellerId,$customerId);$this->settlement->settle($tenantId,$paymentId,$invoiceId,$amount);return$this->commission->settlePayment($tenantId,$paymentId,$customerId,$amount);}
}
