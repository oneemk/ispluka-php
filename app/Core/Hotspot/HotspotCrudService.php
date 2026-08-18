<?php

declare(strict_types=1);

namespace Ispluka\Core\Hotspot;

use PDO;
use InvalidArgumentException;
use RuntimeException;

final class HotspotCrudService
{
    public function __construct(private readonly PDO $pdo, private readonly HotspotValidityService $validity) {}

    public function createProfile(int $tenantId, array $data): int
    {
        $this->tenant($tenantId);
        $name = trim((string) ($data['name'] ?? ''));
        $code = trim((string) ($data['code'] ?? ''));
        $duration = ValidityDuration::parse((string) ($data['validity'] ?? ''));
        if ($name === '' || $code === '') throw new InvalidArgumentException('Profile name and code are required.');
        $s = $this->pdo->prepare("INSERT INTO hotspot_profiles(tenant_id,name,code,validity_expression,validity_seconds,activation_mode,rate_limit,data_limit_bytes,session_limit_seconds,shared_users,status) VALUES(:t,:n,:c,:v,:s,'first_login',:r,:d,:sl,:su,'active') RETURNING id");
        $s->execute([':t'=>$tenantId,':n'=>$name,':c'=>$code,':v'=>$duration->normalized,':s'=>$duration->seconds,':r'=>$data['rate_limit']??null,':d'=>$data['data_limit_bytes']??null,':sl'=>$data['session_limit_seconds']??null,':su'=>max(1,(int)($data['shared_users']??1))]);
        return (int) $s->fetchColumn();
    }

    public function updateProfile(int $tenantId, int $profileId, array $data): void
    {
        $this->tenant($tenantId);
        $fields=[]; $params=[':t'=>$tenantId,':i'=>$profileId];
        foreach (['name','code','rate_limit','data_limit_bytes','session_limit_seconds','shared_users','status'] as $key) {
            if (array_key_exists($key,$data)) { $fields[]="$key=:{$key}"; $params[":{$key}"]=$key==='shared_users'?max(1,(int)$data[$key]):$data[$key]; }
        }
        if (array_key_exists('validity',$data)) { $d=ValidityDuration::parse((string)$data['validity']); $fields[]='validity_expression=:ve'; $fields[]='validity_seconds=:vs'; $params[':ve']=$d->normalized; $params[':vs']=$d->seconds; }
        if (!$fields) throw new InvalidArgumentException('No profile fields supplied.');
        $s=$this->pdo->prepare('UPDATE hotspot_profiles SET '.implode(',',$fields).',updated_at=CURRENT_TIMESTAMP WHERE tenant_id=:t AND id=:i');
        $s->execute($params); if($s->rowCount()!==1) throw new RuntimeException('Hotspot profile not found.');
    }

    public function deleteProfile(int $tenantId, int $profileId): void
    {
        $this->tenant($tenantId);
        $c=$this->pdo->prepare('SELECT COUNT(*) FROM hotspot_users WHERE tenant_id=:t AND profile_id=:i'); $c->execute([':t'=>$tenantId,':i'=>$profileId]);
        if((int)$c->fetchColumn()>0) throw new RuntimeException('Cannot delete a profile that has Hotspot users.');
        $s=$this->pdo->prepare('DELETE FROM hotspot_profiles WHERE tenant_id=:t AND id=:i'); $s->execute([':t'=>$tenantId,':i'=>$profileId]);
        if($s->rowCount()!==1) throw new RuntimeException('Hotspot profile not found.');
    }

    public function createUser(int $tenantId, array $data): int
    {
        $this->tenant($tenantId);
        $username=trim((string)($data['username']??'')); $password=(string)($data['password']??''); $profileId=(int)($data['profile_id']??0); $routerId=(int)($data['router_id']??0);
        if($username===''||$password===''||$profileId<1||$routerId<1) throw new InvalidArgumentException('Username, password, profile and router are required.');
        $p=$this->pdo->prepare('SELECT id FROM hotspot_profiles WHERE tenant_id=:t AND id=:i AND status=\'active\''); $p->execute([':t'=>$tenantId,':i'=>$profileId]); if(!$p->fetchColumn()) throw new RuntimeException('Hotspot profile not found.');
        $dup=$this->pdo->prepare('SELECT 1 FROM hotspot_users WHERE tenant_id=:t AND username=:u'); $dup->execute([':t'=>$tenantId,':u'=>$username]); if($dup->fetchColumn()) throw new RuntimeException('Hotspot username already exists.');
        $s=$this->pdo->prepare("INSERT INTO hotspot_users(tenant_id,router_id,profile_id,username,password,status,mac_address,activated_at,expires_at,created_at,updated_at) VALUES(:t,:r,:p,:u,:pw,'unused',:mac,NULL,NULL,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP) RETURNING id");
        $s->execute([':t'=>$tenantId,':r'=>$routerId,':p'=>$profileId,':u'=>$username,':pw'=>password_hash($password,PASSWORD_DEFAULT),':mac'=>$data['mac_address']??null]);
        return (int)$s->fetchColumn();
    }

    public function updateUser(int $tenantId, int $userId, array $data): void
    {
        $this->tenant($tenantId); $fields=[]; $params=[':t'=>$tenantId,':i'=>$userId];
        foreach(['username','mac_address'] as $key) if(array_key_exists($key,$data)){$fields[]="$key=:{$key}";$params[":{$key}"]=trim((string)$data[$key]);}
        if(array_key_exists('password',$data) && (string)$data['password']!==''){$fields[]='password=:pw';$params[':pw']=password_hash((string)$data['password'],PASSWORD_DEFAULT);}
        if(array_key_exists('profile_id',$data)){$fields[]='profile_id=:p';$params[':p']=(int)$data['profile_id'];}
        if(!$fields) throw new InvalidArgumentException('No Hotspot user fields supplied.');
        $s=$this->pdo->prepare('UPDATE hotspot_users SET '.implode(',',$fields).',updated_at=CURRENT_TIMESTAMP WHERE tenant_id=:t AND id=:i AND status<>\'expired\'');$s->execute($params);if($s->rowCount()!==1)throw new RuntimeException('Hotspot user not found or expired.');
    }

    public function setUserStatus(int $tenantId,int $userId,string $status):void{if(!in_array($status,['disabled','unused'],true))throw new InvalidArgumentException('Invalid Hotspot user status.');$s=$this->pdo->prepare("UPDATE hotspot_users SET status=:s,updated_at=CURRENT_TIMESTAMP WHERE tenant_id=:t AND id=:i AND status<>'expired'");$s->execute([':s'=>$status,':t'=>$tenantId,':i'=>$userId]);if($s->rowCount()!==1)throw new RuntimeException('Hotspot user not found or expired.');}

    public function activateUser(int $tenantId,int $userId):array { return $this->validity->activate($tenantId,$userId); }
    public function expireDue(int $tenantId):int { return $this->validity->expireDue($tenantId); }
    private function tenant(int $tenantId):void { if($tenantId<1) throw new RuntimeException('Invalid tenant context.'); }
}
