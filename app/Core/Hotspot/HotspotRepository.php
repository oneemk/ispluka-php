<?php

declare(strict_types=1);
namespace Ispluka\Core\Hotspot;
use PDO;
use RuntimeException;
final class HotspotRepository {
 public function __construct(private readonly PDO $pdo) {}
 private function tenant(int $tenantId):void { if($tenantId<1) throw new RuntimeException('Invalid tenant context.'); }
 public function profiles(int $tenantId):array{$this->tenant($tenantId);$s=$this->pdo->prepare('SELECT id,name,code,validity_expression,validity_seconds,activation_mode,rate_limit,data_limit_bytes,session_limit_seconds,shared_users,status,created_at FROM hotspot_profiles WHERE tenant_id=:t ORDER BY id DESC');$s->execute([':t'=>$tenantId]);return$s->fetchAll(PDO::FETCH_ASSOC);}
 public function users(int $tenantId):array{$this->tenant($tenantId);$s=$this->pdo->prepare('SELECT u.id,u.username,u.status,u.activated_at,u.expires_at,u.mac_address,p.name AS profile_name,p.code AS profile_code FROM hotspot_users u JOIN hotspot_profiles p ON p.id=u.profile_id AND p.tenant_id=u.tenant_id WHERE u.tenant_id=:t ORDER BY u.id DESC');$s->execute([':t'=>$tenantId]);return$s->fetchAll(PDO::FETCH_ASSOC);}
 public function sessions(int $tenantId,bool $activeOnly=true):array{$this->tenant($tenantId);$sql='SELECT s.id,s.hotspot_user_id,s.router_id,s.client_ip,s.mac_address,s.started_at,s.ended_at,s.bytes_in,s.bytes_out,s.status,u.username FROM hotspot_sessions s JOIN hotspot_users u ON u.id=s.hotspot_user_id AND u.tenant_id=s.tenant_id WHERE s.tenant_id=:t';if($activeOnly)$sql.=" AND s.status='active'";$sql.=' ORDER BY s.started_at DESC';$s=$this->pdo->prepare($sql);$s->execute([':t'=>$tenantId]);return$s->fetchAll(PDO::FETCH_ASSOC);}
 public function bindings(int $tenantId):array{return$this->listRouterRows($tenantId,'hotspot_ip_bindings','id,address,mac_address,to_address,type,comment,disabled,created_at');}
 public function hosts(int $tenantId):array{return$this->listRouterRows($tenantId,'hotspot_hosts','id,address,mac_address,uptime_seconds,bytes_in,bytes_out,last_seen_at');}
 public function walledGarden(int $tenantId):array{return$this->listRouterRows($tenantId,'hotspot_walled_garden','id,dst_host,dst_path,action,comment,disabled,created_at');}
 public function addressLists(int $tenantId):array{return$this->listRouterRows($tenantId,'hotspot_address_lists','id,list_name,address,timeout_seconds,comment,disabled,created_at');}
 public function logs(int $tenantId):array{$this->tenant($tenantId);$s=$this->pdo->prepare('SELECT id,router_id,hotspot_user_id,actor_user_id,action,status,details,created_at FROM hotspot_operation_logs WHERE tenant_id=:t ORDER BY id DESC LIMIT 200');$s->execute([':t'=>$tenantId]);return$s->fetchAll(PDO::FETCH_ASSOC);}
 private function listRouterRows(int $tenantId,string $table,string $columns):array{$this->tenant($tenantId);$s=$this->pdo->prepare("SELECT {$columns},router_id FROM {$table} WHERE tenant_id=:t ORDER BY id DESC");$s->execute([':t'=>$tenantId]);return$s->fetchAll(PDO::FETCH_ASSOC);}
}
