<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use Ispluka\Core\Database\Database;
final class NetworkJobService {
 public function __construct(private readonly Database $db) {}
 public function enqueue(int $tenantId,int $serviceId,string $action,array $payload=[]):int{$s=$this->db->pdo()->prepare('INSERT INTO network_jobs(tenant_id,service_id,action,payload) VALUES(:t,:s,:a,:p) RETURNING id');$s->execute([':t'=>$tenantId,':s'=>$serviceId,':a'=>$action,':p'=>json_encode($payload,JSON_THROW_ON_ERROR)]);return(int)$s->fetchColumn();}
 public function claim(int $limit=20):array{$pdo=$this->db->pdo();$pdo->beginTransaction();try{$s=$pdo->prepare("SELECT id FROM network_jobs WHERE status='pending' AND available_at<=CURRENT_TIMESTAMP ORDER BY id FOR UPDATE SKIP LOCKED LIMIT :l");$s->bindValue(':l',min(max($limit,1),100),\PDO::PARAM_INT);$s->execute();$ids=$s->fetchAll(\PDO::FETCH_COLUMN);if($ids){$u=$pdo->prepare("UPDATE network_jobs SET status='processing',attempts=attempts+1,updated_at=CURRENT_TIMESTAMP WHERE id=ANY(:ids::bigint[])");$u->execute([':ids'=>'{'.implode(',',$ids).'}']);}$pdo->commit();return$ids;}catch(\Throwable$e){$pdo->rollBack();throw$e;}}
}
