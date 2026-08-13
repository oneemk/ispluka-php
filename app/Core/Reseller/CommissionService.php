<?php

declare(strict_types=1);
namespace Ispluka\Core\Reseller;
use Ispluka\Core\Database\Database;
final class CommissionService {
 public function __construct(private readonly Database $db,private readonly ResellerLedgerService $ledger) {}
 public function settlePayment(int $tenantId,int $paymentId,int $customerId,int $amount):?int{$s=$this->db->pdo()->prepare('SELECT c.reseller_id,r.commission_percent FROM customers c JOIN reseller_profiles r ON r.id=c.reseller_id AND r.tenant_id=c.tenant_id WHERE c.tenant_id=:t AND c.id=:c AND r.active');$s->execute([':t'=>$tenantId,':c'=>$customerId]);$r=$s->fetch();if(!$r)return null;$commission=(int)floor($amount*((float)$r['commission_percent']/100));if($commission>0)$this->ledger->credit($tenantId,(int)$r['reseller_id'],$commission,'commission','payment',$paymentId,'Payment commission');return$commission;}
}
