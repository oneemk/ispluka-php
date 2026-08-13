<?php

declare(strict_types=1);
namespace Ispluka\Core\Automation;
use Ispluka\Core\Database\Database;
use Ispluka\Core\Network\MikrotikAutomationService;
final class NetworkJobWorker {
 public function __construct(private readonly Database $db,private readonly MikrotikAutomationService $network) {}
 public function run(int $limit=20):array { $pdo=$this->db->pdo();$s=$pdo->prepare("SELECT * FROM network_jobs WHERE status='pending' AND available_at<=CURRENT_TIMESTAMP ORDER BY id FOR UPDATE SKIP LOCKED LIMIT :l");$pdo->beginTransaction();$s->bindValue(':l',min(max($limit,1),100),\PDO::PARAM_INT);$s->execute();$jobs=$s->fetchAll();$pdo->commit();$done=0;$failed=0;foreach($jobs as $j){$u=$pdo->prepare("UPDATE network_jobs SET status='processing',attempts=attempts+1,updated_at=CURRENT_TIMESTAMP WHERE id=:id AND status='pending'");$u->execute([':id'=>$j['id']]);if($u->rowCount()!==1)continue;try{match($j['action']){'provision'=>$this->network->provision((int)$j['tenant_id'],(int)$j['service_id']),'suspend'=>$this->network->suspend((int)$j['tenant_id'],(int)$j['service_id']),'restore'=>$this->network->restore((int)$j['tenant_id'],(int)$j['service_id']),default=>throw new \RuntimeException('Unknown network action')};$pdo->prepare("UPDATE network_jobs SET status='completed',updated_at=CURRENT_TIMESTAMP WHERE id=:id")->execute([':id'=>$j['id']]);$done++;}catch(\Throwable $e){$attempt=(int)$j['attempts']+1;$delay=min(3600,30*(2**min($attempt,6)));$status=$attempt>=(int)$j['max_attempts']?'failed':'pending';$pdo->prepare("UPDATE network_jobs SET status=:s,last_error=:e,available_at=CURRENT_TIMESTAMP + (:d || ' seconds')::interval,updated_at=CURRENT_TIMESTAMP WHERE id=:id")->execute([':s'=>$status,':e'=>substr($e->getMessage(),0,1000),':d'=>$delay,':id'=>$j['id']]);$failed++;}}return['completed'=>$done,'failed_or_requeued'=>$failed];}
}
