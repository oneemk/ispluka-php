<?php

declare(strict_types=1);
namespace Ispluka\Core\Payments;
use Ispluka\Core\Billing\PaymentAllocator;
use Ispluka\Core\Database\Database;
use RuntimeException;
final class WebhookProcessor {
 public function __construct(private readonly Database $db,private readonly GatewayRegistry $gateways,private readonly PaymentProcessor $payments,private readonly PaymentAllocator $allocator) {}
 public function handle(int $tenantId,string $gateway,array $payload):array { $verified=$this->gateways->get($gateway)->verify($payload);if(($verified['status']??'')!=='verified')throw new RuntimeException('Payment verification failed.');$customerId=(int)($payload['customer_id']??0);$amount=(int)($verified['amount']??0);$invoiceId=(int)($payload['invoice_id']??0);if($customerId<=0||$amount<=0||$invoiceId<=0)throw new RuntimeException('Incomplete payment callback.');$tx=(string)$verified['transaction_id'];$paymentId=$this->payments->recordVerified($tenantId,$customerId,$amount,$gateway,$tx,$payload);$this->allocator->allocate($tenantId,$paymentId,$invoiceId,$amount);return['payment_id'=>$paymentId,'invoice_id'=>$invoiceId,'status'=>'allocated'];}
}
