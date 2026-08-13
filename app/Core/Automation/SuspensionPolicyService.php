<?php

declare(strict_types=1);
namespace Ispluka\Core\Automation;
use Ispluka\Core\Database\Database;
use Ispluka\Core\Network\NetworkJobService;
final class SuspensionPolicyService {
 public function __construct(private readonly Database $db,private readonly NetworkJobService $jobs) {}
 public function enforce(int $limit=200):array { $pdo=$this->db->pdo();$s=$pdo->prepare("SELECT cs.tenant_id,cs.id service_id,b.grace_days FROM customer_services cs JOIN billing_policies b ON b.tenant_id=cs.tenant_id WHERE cs.status='active' AND b.auto_suspend AND b.suspend_on_overdue AND EXISTS (SELECT 1 FROM invoices i WHERE i.tenant_id=cs.tenant_id AND i.service_id=cs.id AND i.status='overdue' AND i.due_date + b.grace_days < CURRENT_DATE) ORDER BY cs.id LIMIT :l");$s->bindValue(':l',min(max($limit,1),500),\PDO::PARAM_INT);$s->execute();$queued=0;foreach($s->fetchAll() as $r){$u=$pdo->prepare("UPDATE customer_services SET status='suspended',suspended_at=CURRENT_TIMESTAMP,suspended_reason='Billing overdue',updated_at=CURRENT_TIMESTAMP WHERE tenant_id=:t AND id=:id AND status='active'");$u->execute([':t'=>$r['tenant_id'],':id'=>$r['service_id']]);if($u->rowCount()===1){$this->jobs->enqueue((int)$r['tenant_id'],(int)$r['service_id'],'suspend');$queued++;}}return['suspended_jobs'=>$queued];}
 public function restoreForCustomer(int $tenantId,int $customerId):int { $pdo=$this->db->pdo();$s=$pdo->prepare("SELECT cs.id FROM customer_services cs WHERE cs.tenant_id=:t AND cs.customer_id=:c AND cs.status='suspended' AND EXISTS(SELECT 1 FROM billing_policies b WHERE b.tenant_id=cs.tenant_id AND b.auto_restore) AND NOT EXISTS(SELECT 1 FROM invoices i WHERE i.tenant_id=cs.tenant_id AND i.service_id=cs.id AND i.status='overdue')");$s->execute([':t'=>$tenantId,':c'=>$customerId]);$n=0;foreach($s->fetchAll() as $r){$u=$pdo->prepare("UPDATE customer_services SET status='active',suspended_at=NULL,suspended_reason=NULL,updated_at=CURRENT_TIMESTAMP WHERE tenant_id=:t AND id=:id AND status='suspended'");$u->execute([':t'=>$tenantId,':id'=>$r['id']]);if($u->rowCount()===1){$this->jobs->enqueue($tenantId,(int)$r['id'],'restore');$n++;}}return$n;}
}
