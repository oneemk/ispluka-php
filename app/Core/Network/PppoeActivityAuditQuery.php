<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use PDO;
final class PppoeActivityAuditQuery
{
 public function __construct(private readonly PDO $pdo){}
 public function inactiveForDays(int $tenantId,int $days=20,int $limit=500):array{$days=max(1,$days);$limit=max(1,min(1000,$limit));$q=$this->pdo->prepare("SELECT * FROM pppoe_activity_state WHERE tenant_id=:t AND last_seen_at IS NOT NULL AND last_seen_at <= CURRENT_TIMESTAMP - (:d * INTERVAL '1 day') ORDER BY last_seen_at ASC LIMIT $limit");$q->execute([':t'=>$tenantId,':d'=>$days]);return$q->fetchAll(PDO::FETCH_ASSOC);}
 public function summary(int $tenantId):array{$q=$this->pdo->prepare("SELECT COUNT(*) FILTER(WHERE online=true)::int active, COUNT(*) FILTER(WHERE online=false)::int inactive, COUNT(*) FILTER(WHERE last_seen_at IS NULL OR last_seen_at <= CURRENT_TIMESTAMP - INTERVAL '20 days')::int inactive_20d FROM pppoe_activity_state WHERE tenant_id=:t");$q->execute([':t'=>$tenantId]);return$q->fetch(PDO::FETCH_ASSOC)?:['active'=>0,'inactive'=>0,'inactive_20d'=>0];}
}
