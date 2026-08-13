<?php

declare(strict_types=1);
namespace Ispluka\Core\Reseller;
use Ispluka\Core\Database\Database;
use RuntimeException;
final class ResellerLedgerService {
 public function __construct(private readonly Database $db) {}
 public function credit(int $tenantId,int $resellerId,int $amount,string $type='commission',?string $referenceType=null,?int $referenceId=null,?string $note=null):void{$this->entry($tenantId,$resellerId,$amount,$type,$referenceType,$referenceId,$note,1);}
 public function debit(int $tenantId,int $resellerId,int $amount,string $type='debit',?string $referenceType=null,?int $referenceId=null,?string $note=null):void{$this->entry($tenantId,$resellerId,$amount,$type,$referenceType,$referenceId,$note,-1);}
 private function entry(int $tenantId,int $resellerId,int $amount,string $type,?string $referenceType,?int $referenceId,?string $note,int $direction):void{$amount=abs($amount);if($amount===0)throw new RuntimeException('Ledger amount must be positive.');$pdo=$this->db->pdo();$pdo->beginTransaction();try{$s=$pdo->prepare('SELECT balance,credit_limit FROM reseller_profiles WHERE tenant_id=:t AND id=:r AND active FOR UPDATE');$s->execute([':t'=>$tenantId,':r'=>$resellerId]);$r=$s->fetch();if(!$r)throw new RuntimeException('Reseller not found.');$new=(int)$r['balance']+($direction*$amount);if($new<-(int)$r['credit_limit'])throw new RuntimeException('Reseller credit limit exceeded.');$i=$pdo->prepare('INSERT INTO reseller_ledger(tenant_id,reseller_id,type,amount,reference_type,reference_id,note) VALUES(:t,:r,:ty,:a,:rt,:ri,:n)');$i->execute([':t'=>$tenantId,':r'=>$resellerId,':ty'=>$type,':a'=>$direction*$amount,':rt'=>$referenceType,':ri'=>$referenceId,':n'=>$note]);$u=$pdo->prepare('UPDATE reseller_profiles SET balance=:b,updated_at=CURRENT_TIMESTAMP WHERE tenant_id=:t AND id=:r');$u->execute([':b'=>$new,':t'=>$tenantId,':r'=>$resellerId]);$pdo->commit();}catch(\Throwable $e){$pdo->rollBack();throw$e;}}
}
