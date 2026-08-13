<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use PDO;
final class PppoeEnforcementAuditQuery
{
 public function __construct(private readonly PDO $pdo){}
 public function list(int $tenantId,?string $status=null,int $limit=100,int $offset=0):array{$limit=max(1,min(500,$limit));$offset=max(0,$offset);$sql='SELECT * FROM pppoe_enforcement_log WHERE tenant_id=:t';$args=[':t'=>$tenantId];if($status!==null&&in_array($status,['success','failed','mismatch'],true)){$sql.=' AND status=:s';$args[':s']=$status;}$sql.=' ORDER BY created_at DESC LIMIT '.$limit.' OFFSET '.$offset;$q=$this->pdo->prepare($sql);$q->execute($args);return$q->fetchAll(PDO::FETCH_ASSOC);}
 public function summary(int $tenantId):array{$q=$this->pdo->prepare("SELECT status,COUNT(*)::int total FROM pppoe_enforcement_log WHERE tenant_id=:t GROUP BY status");$q->execute([':t'=>$tenantId]);$out=['success'=>0,'failed'=>0,'mismatch'=>0];foreach($q->fetchAll(PDO::FETCH_ASSOC) as $r)$out[$r['status']]=(int)$r['total'];return$out;}
}
