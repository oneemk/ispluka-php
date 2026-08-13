<?php

declare(strict_types=1);
namespace Ispluka\Core\Packages;
use Ispluka\Core\Database\Database;
use RuntimeException;
final class PackageService {
 public function __construct(private readonly Database $db) {}
 public function create(int $tenantId,array $d):int { $name=trim((string)($d['name']??'')); if($name==='')throw new RuntimeException('Package name is required.'); $type=strtolower((string)($d['connection_type']??'pppoe')); if(!in_array($type,['pppoe','hotspot'],true))throw new RuntimeException('Invalid connection type.'); $s=$this->db->pdo()->prepare('INSERT INTO packages(tenant_id,name,download_kbps,upload_kbps,monthly_price,connection_type,shared_users) VALUES(:t,:n,:d,:u,:p,:c,:s) RETURNING id');$s->execute([':t'=>$tenantId,':n'=>$name,':d'=>(int)$d['download_kbps'],':u'=>(int)$d['upload_kbps'],':p'=>(int)$d['monthly_price'],':c'=>$type,':s'=>max(1,(int)($d['shared_users']??1))]);return(int)$s->fetchColumn(); }
 public function list(int $tenantId):array{$s=$this->db->pdo()->prepare("SELECT * FROM packages WHERE tenant_id=:t AND status='active' ORDER BY monthly_price,name");$s->execute([':t'=>$tenantId]);return$s->fetchAll();}
}
