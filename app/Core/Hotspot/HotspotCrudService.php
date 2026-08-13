<?php

declare(strict_types=1);
namespace Ispluka\Core\Hotspot;
use PDO;
use InvalidArgumentException;
use RuntimeException;
final class HotspotCrudService
{
 public function __construct(private readonly PDO $pdo) {}
 public function createProfile(int $tenantId,array $data):int{$name=trim((string)($data['name']??''));$code=trim((string)($data['code']??''));$duration=ValidityDuration::parse((string)($data['validity']??''));if($name===''||$code==='')throw new InvalidArgumentException('Profile name and code are required.');$s=$this->pdo->prepare("INSERT INTO hotspot_profiles(tenant_id,name,code,validity_expression,validity_seconds,activation_mode,rate_limit,data_limit_bytes,session_limit_seconds,shared_users,status) VALUES(:t,:n,:c,:v,:s,'first_login',:r,:d,:sl,:su,'active') RETURNING id");$s->execute([':t'=>$tenantId,':n'=>$name,':c'=>$code,':v'=>$duration->normalized,':s'=>$duration->seconds,':r'=>$data['rate_limit']??null,':d'=>$data['data_limit_bytes']??null,':sl'=>$data['session_limit_seconds']??null,':su'=>max(1,(int)($data['shared_users']??1))]);return(int)$s->fetchColumn();}
 public function setUserStatus(int $tenantId,int $userId,string $status):void{if(!in_array($status,['disabled','unused'],true))throw new InvalidArgumentException('Invalid Hotspot user status.');$s=$this->pdo->prepare("UPDATE hotspot_users SET status=:s,updated_at=CURRENT_TIMESTAMP WHERE tenant_id=:t AND id=:i AND status<>'expired'");$s->execute([':s'=>$status,':t'=>$tenantId,':i'=>$userId]);if($s->rowCount()!==1)throw new RuntimeException('Hotspot user not found or expired.');}
}
