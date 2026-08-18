<?php

declare(strict_types=1);

namespace Ispluka\Core\Hotspot;

use PDO;
use RuntimeException;
use Throwable;

final class HotspotActionService
{
    public function __construct(private readonly PDO $pdo, private readonly MikroTikHotspotGateway $gateway, private readonly HotspotAuditService $audit) {}

    public function disconnect(int $tenantId,int $sessionId,?int $actorUserId=null):void{
        $s=$this->pdo->prepare("SELECT s.router_id,s.hotspot_user_id,u.username FROM hotspot_sessions s JOIN hotspot_users u ON u.id=s.hotspot_user_id AND u.tenant_id=s.tenant_id WHERE s.tenant_id=:t AND s.id=:i AND s.status='active'");$s->execute([':t'=>$tenantId,':i'=>$sessionId]);$row=$s->fetch(PDO::FETCH_ASSOC);if(!$row||!$row['router_id'])throw new RuntimeException('Active Hotspot session not found.');
        try{$this->gateway->disconnect($tenantId,(int)$row['router_id'],(string)$row['username']);$u=$this->pdo->prepare("UPDATE hotspot_sessions SET status='ended',ended_at=CURRENT_TIMESTAMP WHERE tenant_id=:t AND id=:i AND status='active'");$u->execute([':t'=>$tenantId,':i'=>$sessionId]);$this->audit->record($tenantId,'session.disconnect','success',(int)$row['router_id'],(int)$row['hotspot_user_id'],$actorUserId,['session_id'=>$sessionId,'username'=>$row['username']]);}catch(Throwable $e){$this->audit->record($tenantId,'session.disconnect','failed',(int)$row['router_id'],(int)$row['hotspot_user_id'],$actorUserId,['session_id'=>$sessionId,'error'=>$e->getMessage()]);throw$e;}
    }

    public function syncRouterTime(int $tenantId,int $routerId,int $toleranceSeconds=10,?int $actorUserId=null):array{try{$router=$this->gateway->routerTime($tenantId,$routerId);$result=(new RouterTimeCheckService($this->pdo))->evaluate($tenantId,$routerId,$router,$toleranceSeconds);$this->audit->record($tenantId,'router.time_check','success',$routerId,null,$actorUserId,$result);return$result;}catch(Throwable $e){$this->audit->record($tenantId,'router.time_check','failed',$routerId,null,$actorUserId,['error'=>$e->getMessage()]);throw$e;}}

    public function activeUsers(int $tenantId,int $routerId,?int $actorUserId=null):array{try{$rows=$this->gateway->activeUsers($tenantId,$routerId);$this->audit->record($tenantId,'router.active_users.read','success',$routerId,null,$actorUserId,['count'=>count($rows)]);return$rows;}catch(Throwable $e){$this->audit->record($tenantId,'router.active_users.read','failed',$routerId,null,$actorUserId,['error'=>$e->getMessage()]);throw$e;}}

    public function syncSessions(int $tenantId,int $routerId,?int $actorUserId=null):array
    {
        $rows=$this->activeUsers($tenantId,$routerId,$actorUserId);$started=0;$updated=0;$ended=0;$seen=[];
        foreach($rows as $row){$username=trim((string)($row['username']??''));if($username==='')continue;$u=$this->pdo->prepare('SELECT id FROM hotspot_users WHERE tenant_id=:t AND username=:u LIMIT 1');$u->execute([':t'=>$tenantId,':u'=>$username]);$uid=$u->fetchColumn();if(!$uid)continue;$key=(string)($row['session_id']??($row['id']??$username));$seen[$key]=true;
            $s=$this->pdo->prepare("SELECT id FROM hotspot_sessions WHERE tenant_id=:t AND router_id=:r AND hotspot_user_id=:u AND status='active' AND client_ip IS NOT DISTINCT FROM :ip AND mac_address IS NOT DISTINCT FROM :mac ORDER BY id DESC LIMIT 1");$s->execute([':t'=>$tenantId,':r'=>$routerId,':u'=>$uid,':ip'=>$row['address']??null,':mac'=>$row['mac_address']??null]);$sid=$s->fetchColumn();
            $uptime=$this->durationSeconds((string)($row['uptime']??''));$start=$uptime>0?(new \DateTimeImmutable('now',new \DateTimeZone('UTC')))->modify('-'.$uptime.' seconds'):new \DateTimeImmutable('now',new \DateTimeZone('UTC'));
            if($sid){$q=$this->pdo->prepare('UPDATE hotspot_sessions SET started_at=LEAST(started_at,:st),bytes_in=:bi,bytes_out=:bo WHERE tenant_id=:t AND id=:i');$q->execute([':st'=>$start->format(DATE_ATOM),':bi'=>(int)($row['bytes_in']??0),':bo'=>(int)($row['bytes_out']??0),':t'=>$tenantId,':i'=>$sid]);$updated++;}
            else{$q=$this->pdo->prepare("INSERT INTO hotspot_sessions(tenant_id,hotspot_user_id,router_id,client_ip,mac_address,started_at,bytes_in,bytes_out,status) VALUES(:t,:u,:r,:ip,:mac,:st,:bi,:bo,'active')");$q->execute([':t'=>$tenantId,':u'=>$uid,':r'=>$routerId,':ip'=>$row['address']??null,':mac'=>$row['mac_address']??null,':st'=>$start->format(DATE_ATOM),':bi'=>(int)($row['bytes_in']??0),':bo'=>(int)($row['bytes_out']??0)]);$started++;}
        }
        $q=$this->pdo->prepare("UPDATE hotspot_sessions SET status='ended',ended_at=CURRENT_TIMESTAMP WHERE tenant_id=:t AND router_id=:r AND status='active' AND started_at < CURRENT_TIMESTAMP - INTERVAL '2 seconds' AND hotspot_user_id IN (SELECT id FROM hotspot_users WHERE tenant_id=:t)");
        // Only close sessions that are no longer present when the router returned a successful snapshot.
        if($rows!==[]){$active=$this->pdo->prepare("SELECT s.id FROM hotspot_sessions s WHERE s.tenant_id=:t AND s.router_id=:r AND s.status='active'");$active->execute([':t'=>$tenantId,':r'=>$routerId]);foreach($active->fetchAll(PDO::FETCH_COLUMN) as $sid){$one=$this->pdo->prepare('SELECT hotspot_user_id,client_ip,mac_address FROM hotspot_sessions WHERE tenant_id=:t AND id=:i');$one->execute([':t'=>$tenantId,':i'=>$sid]);$r=$one->fetch(PDO::FETCH_ASSOC);$user=$this->pdo->prepare('SELECT username FROM hotspot_users WHERE tenant_id=:t AND id=:i');$user->execute([':t'=>$tenantId,':i'=>$r['hotspot_user_id']??0]);$name=$user->fetchColumn();$match=false;foreach($rows as $ar){if((string)($ar['username']??'')===(string)$name && (string)($ar['address']??'')===(string)($r['client_ip']??'') && (string)($ar['mac_address']??'')===(string)($r['mac_address']??'')){$match=true;break;}}if(!$match){$x=$this->pdo->prepare("UPDATE hotspot_sessions SET status='ended',ended_at=CURRENT_TIMESTAMP WHERE tenant_id=:t AND id=:i AND status='active'");$x->execute([':t'=>$tenantId,':i'=>$sid]);if($x->rowCount())$ended++;}}}
        $this->audit->record($tenantId,'session.sync','success',$routerId,null,$actorUserId,['started'=>$started,'updated'=>$updated,'ended'=>$ended]);return['started'=>$started,'updated'=>$updated,'ended'=>$ended,'active_snapshot'=>count($rows)];
    }

    private function durationSeconds(string $value):int{if($value==='')return 0;$total=0;if(preg_match_all('/(\d+)w/i',$value,$m))foreach($m[1] as $v)$total+=(int)$v*604800;if(preg_match_all('/(\d+)d/i',$value,$m))foreach($m[1] as $v)$total+=(int)$v*86400;if(preg_match_all('/(\d+)h/i',$value,$m))foreach($m[1] as $v)$total+=(int)$v*3600;if(preg_match_all('/(\d+)m/i',$value,$m))foreach($m[1] as $v)$total+=(int)$v*60;if(preg_match_all('/(\d+)s/i',$value,$m))foreach($m[1] as $v)$total+=(int)$v;return$total;}

    public function createRouterUser(int $tenantId,int $routerId,array $attributes,?int $actorUserId=null):void{try{$this->gateway->createUser($tenantId,$routerId,$attributes);$this->audit->record($tenantId,'user.create','success',$routerId,null,$actorUserId,['username'=>$attributes['name']??null]);}catch(Throwable $e){$this->audit->record($tenantId,'user.create','failed',$routerId,null,$actorUserId,['username'=>$attributes['name']??null,'error'=>$e->getMessage()]);throw$e;}}
    public function updateRouterUser(int $tenantId,int $routerId,string $username,array $attributes,?int $actorUserId=null):void{try{$this->gateway->updateUser($tenantId,$routerId,$username,$attributes);$this->audit->record($tenantId,'user.update','success',$routerId,null,$actorUserId,['username'=>$username]);}catch(Throwable $e){$this->audit->record($tenantId,'user.update','failed',$routerId,null,$actorUserId,['username'=>$username,'error'=>$e->getMessage()]);throw$e;}}
    public function disableRouterUser(int $tenantId,int $routerId,string $username,?int $actorUserId=null):void{$this->toggleRouterUser($tenantId,$routerId,$username,false,$actorUserId);}
    public function enableRouterUser(int $tenantId,int $routerId,string $username,?int $actorUserId=null):void{$this->toggleRouterUser($tenantId,$routerId,$username,true,$actorUserId);}
    private function toggleRouterUser(int $tenantId,int $routerId,string $username,bool $enable,?int $actorUserId):void{$action=$enable?'user.enable':'user.disable';try{$enable?$this->gateway->enableUser($tenantId,$routerId,$username):$this->gateway->disableUser($tenantId,$routerId,$username);$this->audit->record($tenantId,$action,'success',$routerId,null,$actorUserId,['username'=>$username]);}catch(Throwable $e){$this->audit->record($tenantId,$action,'failed',$routerId,null,$actorUserId,['username'=>$username,'error'=>$e->getMessage()]);throw$e;}}
}
