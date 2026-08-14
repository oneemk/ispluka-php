<?php

declare(strict_types=1);
namespace Ispluka\Core\Automation;
use Ispluka\Core\Database\Database;
use Ispluka\Core\Network\NetworkJobService;
final class SuspensionPolicyService {
 public function __construct(private readonly Database $db,private readonly NetworkJobService $jobs) {}
 public function enforce(int $limit=200):array { $pdo=$this->db->pdo();$s=$pdo->prepare("SELECT cs.tenant_id,cs.id service_id FROM customer_services cs JOIN billing_policies b ON b.tenant_id=cs.tenant_id WHERE cs.status='active' AND b.auto_suspend AND b.suspend_on_overdue AND EXISTS (SELECT 1 FROM invoices i WHERE i.tenant_id=cs.tenant_id AND i.service_id=cs.id AND i.status='overdue' AND i.due_date + b.grace_days < CURRENT_DATE) AND NOT EXISTS (SELECT 1 FROM network_jobs j WHERE j.tenant_id=cs.tenant_id AND j.service_id=cs.id AND j.action='suspend' AND j.status IN ('pending','processing')) ORDER BY cs.id LIMIT :l");$s->bindValue(':l',min(max($limit,1),500),\PDO::PARAM_INT);$s->execute();$queued=0;foreach($s->fetchAll() as $r){$this->jobs->enqueue((int)$r['tenant_id'],(int)$r['service_id'],'suspend',['reason'=>'Billing overdue']);$queued++;}return['suspended_jobs'=>$queued];}
 public function restoreForCustomer(int $tenantId,int $customerId):int { $pdo=$this->db->pdo();$s=$pdo->prepare("SELECT cs.id FROM customer_services cs WHERE cs.tenant_id=:t AND cs.customer_id=:c AND cs.status='suspended' AND EXISTS(SELECT 1 FROM billing_policies b WHERE b.tenant_id=cs.tenant_id AND b.auto_restore) AND NOT EXISTS(SELECT 1 FROM invoices i WHERE i.tenant_id=cs.tenant_id AND i.service_id=cs.id AND i.status='overdue') AND NOT EXISTS(SELECT 1 FROM network_jobs j WHERE j.tenant_id=cs.tenant_id AND j.service_id=cs.id AND j.action='restore' AND j.status IN ('pending','processing'))");$s->execute([':t'=>$tenantId,':c'=>$customerId]);$n=0;foreach($s->fetchAll() as $r){$this->jobs->enqueue($tenantId,(int)$r['id'],'restore',['reason'=>'Payment cleared']);$n++;}return$n;}
}
