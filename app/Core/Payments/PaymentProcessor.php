<?php

declare(strict_types=1);
namespace Ispluka\Core\Payments;
use Ispluka\Core\Database\Database;
use RuntimeException;
final class PaymentProcessor {
 public function __construct(private readonly Database $db) {}
 public function recordVerified(int $tenantId,int $customerId,int $amount,string $gateway,string $transactionId,array $meta=[]):int {
  if($amount<=0||trim($transactionId)==='')throw new RuntimeException('Invalid payment.');
  $pdo=$this->db->pdo();$pdo->beginTransaction();
  try{$s=$pdo->prepare('INSERT INTO payments(tenant_id,customer_id,amount,method,transaction_id,status,paid_at,metadata) VALUES(:t,:c,:a,:m,:x,\'completed\',CURRENT_TIMESTAMP,:meta) ON CONFLICT(tenant_id,transaction_id) DO UPDATE SET updated_at=CURRENT_TIMESTAMP RETURNING id');$s->execute([':t'=>$tenantId,':c'=>$customerId,':a'=>$amount,':m'=>$gateway,':x'=>$transactionId,':meta'=>json_encode($meta,JSON_THROW_ON_ERROR)]);$id=(int)$s->fetchColumn();$pdo->commit();return$id;}catch(\Throwable$e){$pdo->rollBack();throw$e;}
 }
}
