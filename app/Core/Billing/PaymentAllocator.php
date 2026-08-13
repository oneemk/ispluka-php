<?php

declare(strict_types=1);
namespace Ispluka\Core\Billing;
use Ispluka\Core\Database\Database;
use RuntimeException;
final class PaymentAllocator {
 public function __construct(private readonly Database $db) {}
 public function allocate(int $tenantId,int $paymentId,int $invoiceId,int $amount):void {
  if($amount<=0)throw new RuntimeException('Payment allocation must be positive.');$pdo=$this->db->pdo();$pdo->beginTransaction();
  try{$s=$pdo->prepare("SELECT i.id,i.total,i.paid_amount,p.amount payment_amount FROM invoices i JOIN payments p ON p.id=:p WHERE i.tenant_id=:t AND i.id=:i AND p.tenant_id=:t AND p.status='completed' FOR UPDATE");$s->execute([':t'=>$tenantId,':i'=>$invoiceId,':p'=>$paymentId]);$r=$s->fetch();if(!$r)throw new RuntimeException('Invoice/payment not found.');$remaining=(int)$r['total']-(int)$r['paid_amount'];if($amount>$remaining||$amount>(int)$r['payment_amount'])throw new RuntimeException('Allocation exceeds available amount.');$a=$pdo->prepare('INSERT INTO payment_allocations(payment_id,invoice_id,amount) VALUES(:p,:i,:a)');$a->execute([':p'=>$paymentId,':i'=>$invoiceId,':a'=>$amount]);$status=$amount+$r['paid_amount']==$r['total']?'paid':'partial';$u=$pdo->prepare('UPDATE invoices SET paid_amount=paid_amount+:a,status=:s,updated_at=CURRENT_TIMESTAMP WHERE id=:i AND tenant_id=:t');$u->execute([':a'=>$amount,':s'=>$status,':i'=>$invoiceId,':t'=>$tenantId]);$pdo->commit();}catch(\Throwable $e){$pdo->rollBack();throw $e;}
 }
}
