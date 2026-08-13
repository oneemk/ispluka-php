<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use PDO;
final class PppoeActivityQuery
{
 public function __construct(private readonly PDO $pdo){}
 public function list(int $tenantId,bool $online, int $limit=100,int $offset=0):array{$limit=max(1,min(500,$limit));$offset=max(0,$offset);$q=$this->pdo->prepare('SELECT * FROM pppoe_activity_state WHERE tenant_id=:t AND online=:o ORDER BY last_seen_at DESC NULLS LAST LIMIT '.$limit.' OFFSET '.$offset);$q->execute([':t'=>$tenantId,':o'=>$online]);return$q->fetchAll(PDO::FETCH_ASSOC);}
 public function live(int $tenantId,int $routerId,string $username):?array{$q=$this->pdo->prepare('SELECT * FROM pppoe_activity_state WHERE tenant_id=:t AND router_id=:r AND username=:u LIMIT 1');$q->execute([':t'=>$tenantId,':r'=>$routerId,':u'=>$username]);$row=$q->fetch(PDO::FETCH_ASSOC);return$row?:null;}
}
