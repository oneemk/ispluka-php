<?php

declare(strict_types=1);
namespace Ispluka\Core\Hotspot;
use Ispluka\Core\Database\Database;
final class RouterTimeCheckService {
 public function __construct(private readonly Database $db) {}
 public function evaluate(int $tenantId,int $routerId,\DateTimeImmutable $routerTime,int $toleranceSeconds=10):array{$server=new \DateTimeImmutable('now',new \DateTimeZone('UTC'));$difference=$routerTime->getTimestamp()-$server->getTimestamp();$warning=abs($difference)>$toleranceSeconds;$s=$this->db->pdo()->prepare('INSERT INTO hotspot_router_time_checks(tenant_id,router_id,router_time,server_time,difference_seconds,tolerance_seconds,warning) VALUES(:t,:r,:rt,:st,:d,:tol,:w)');$s->execute([':t'=>$tenantId,':r'=>$routerId,':rt'=>$routerTime->format('Y-m-d H:i:sP'),':st'=>$server->format('Y-m-d H:i:sP'),':d'=>$difference,':tol'=>$toleranceSeconds,':w'=>$warning]);return['router_time'=>$routerTime->format(DATE_ATOM),'server_time'=>$server->format(DATE_ATOM),'difference_seconds'=>$difference,'warning'=>$warning,'can_continue'=>true];}
}
