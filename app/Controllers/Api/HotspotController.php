<?php

declare(strict_types=1);

namespace Ispluka\Controllers\Api;

use Ispluka\Core\Api\ApiResponse;
use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Hotspot\HotspotActionService;
use Ispluka\Core\Hotspot\HotspotCrudService;
use Ispluka\Core\Hotspot\HotspotRepository;
use Ispluka\Core\Hotspot\HotspotValidityService;
use Ispluka\Core\Hotspot\MikroTikHotspotGateway;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Security\SecretBox;
use Ispluka\Core\Database\Database;
use PDO;
use RuntimeException;

final class HotspotController
{
    public function __construct(private readonly PDO $pdo, private readonly AuthManager $auth, private readonly SecretBox $secrets, private readonly MikroTikHotspotGateway $gateway) {}
    public function profiles(Request $r): \Ispluka\Core\Http\Response { return ApiResponse::success((new HotspotRepository($this->pdo))->profiles($this->tenant())); }
    public function createProfile(Request $r): \Ispluka\Core\Http\Response { $tenant=$this->tenant();$id=(new HotspotCrudService($this->pdo))->createProfile($tenant,['name'=>$r->input('name'),'code'=>$r->input('code'),'validity'=>$r->input('validity'),'rate_limit'=>$r->input('rate_limit'),'data_limit_bytes'=>$r->input('data_limit_bytes'),'session_limit_seconds'=>$r->input('session_limit_seconds'),'shared_users'=>$r->input('shared_users',1)]);$this->log($tenant,null,null,'profile.create','success',['profile_id'=>$id]);return ApiResponse::success(['id'=>$id],201); }
    public function users(Request $r): \Ispluka\Core\Http\Response { return ApiResponse::success((new HotspotRepository($this->pdo))->users($this->tenant())); }
    public function createUser(Request $r): \Ispluka\Core\Http\Response { $tenant=$this->tenant();$username=trim((string)$r->input('username',''));$password=(string)$r->input('password','');$profileId=(int)$r->input('profile_id',0);$routerId=$r->input('router_id')!==null?(int)$r->input('router_id'):null;if($username===''||$password===''||$profileId<1)return ApiResponse::error('username, password and profile_id are required.',422);$s=$this->pdo->prepare("SELECT id,code FROM hotspot_profiles WHERE id=:id AND tenant_id=:t AND status='active'");$s->execute([':id'=>$profileId,':t'=>$tenant]);$profile=$s->fetch();if(!is_array($profile))return ApiResponse::error('Hotspot profile not found.',404);if($routerId!==null)$this->assertRouter($tenant,$routerId);$s=$this->pdo->prepare("INSERT INTO hotspot_users(tenant_id,profile_id,router_id,username,password_ciphertext,status,mac_address,notes) VALUES(:t,:p,:r,:u,:pw,'unused',:mac,:notes) RETURNING id");$s->execute([':t'=>$tenant,':p'=>$profileId,':r'=>$routerId,':u'=>$username,':pw'=>$this->secrets->encrypt($password),':mac'=>$r->input('mac_address'),':notes'=>$r->input('notes')]);$id=(int)$s->fetchColumn();if($routerId!==null){try{$this->gateway->createUser($routerId,['username'=>$username,'password'=>$password,'profile'=>(string)$profile['code'],'mac-address'=>$r->input('mac_address'),'comment'=>$r->input('notes')]);}catch(\Throwable $e){$this->log($tenant,$routerId,$id,'user.create','failed',['error'=>substr($e->getMessage(),0,500)]);return ApiResponse::error('User was stored but MikroTik provisioning failed.',502);}}$this->log($tenant,$routerId,$id,'user.create','success');return ApiResponse::success(['id'=>$id,'status'=>'unused'],201); }
    public function activate(Request $r): \Ispluka\Core\Http\Response { $tenant=$this->tenant();$id=(int)$r->input('user_id',0);$db=new Database(require dirname(__DIR__,3).'/config/database.php');$result=(new HotspotValidityService($db))->activate($tenant,$id);$this->log($tenant,null,$id,'user.activate','success');return ApiResponse::success($result); }
    public function status(Request $r): \Ispluka\Core\Http\Response { $tenant=$this->tenant();$id=(int)$r->input('user_id',0);$status=(string)$r->input('status','');$s=$this->pdo->prepare('SELECT id,router_id,username,status FROM hotspot_users WHERE id=:id AND tenant_id=:t');$s->execute([':id'=>$id,':t'=>$tenant]);$user=$s->fetch();if(!is_array($user))return ApiResponse::error('Hotspot user not found.',404);if(!in_array($status,['disabled','unused','active'],true))return ApiResponse::error('Invalid Hotspot user status.',422);if($user['router_id']){if($status==='disabled')$this->gateway->disableUser((int)$user['router_id'],(string)$user['username']);elseif($status==='active')$this->gateway->enableUser((int)$user['router_id'],(string)$user['username']);}$u=$this->pdo->prepare("UPDATE hotspot_users SET status=:s,updated_at=CURRENT_TIMESTAMP WHERE id=:id AND tenant_id=:t AND status<>'expired'");$u->execute([':s'=>$status,':id'=>$id,':t'=>$tenant]);$this->log($tenant,$user['router_id']?(int)$user['router_id']:null,$id,'user.'.$status,'success');return ApiResponse::success(['id'=>$id,'status'=>$status]); }
    public function sessions(Request $r): \Ispluka\Core\Http\Response { return ApiResponse::success((new HotspotRepository($this->pdo))->sessions($this->tenant(),$r->query('active_only','1')!=='0')); }
    public function disconnect(Request $r): \Ispluka\Core\Http\Response { $tenant=$this->tenant();$id=(int)$r->input('session_id',0);(new HotspotActionService($this->pdo,$this->gateway))->disconnect($tenant,$id);$this->log($tenant,null,null,'session.disconnect','success',['session_id'=>$id]);return ApiResponse::success(['disconnected'=>true]); }
    public function bindings(Request $r): \Ispluka\Core\Http\Response { return ApiResponse::success((new HotspotRepository($this->pdo))->bindings($this->tenant())); }
    public function hosts(Request $r): \Ispluka\Core\Http\Response { return ApiResponse::success((new HotspotRepository($this->pdo))->hosts($this->tenant())); }
    public function walledGarden(Request $r): \Ispluka\Core\Http\Response { return ApiResponse::success((new HotspotRepository($this->pdo))->walledGarden($this->tenant())); }
    public function addressLists(Request $r): \Ispluka\Core\Http\Response { return ApiResponse::success((new HotspotRepository($this->pdo))->addressLists($this->tenant())); }
    public function logs(Request $r): \Ispluka\Core\Http\Response { return ApiResponse::success((new HotspotRepository($this->pdo))->logs($this->tenant())); }
    public function routerTime(Request $r): \Ispluka\Core\Http\Response { $tenant=$this->tenant();$routerId=(int)$r->query('router_id',$r->input('router_id',0));$this->assertRouter($tenant,$routerId);$router=$this->gateway->routerTime($routerId);$server=new \DateTimeImmutable('now',new \DateTimeZone('UTC'));return ApiResponse::success(['router_time'=>$router->format(DATE_ATOM),'server_time'=>$server->format(DATE_ATOM),'difference_seconds'=>$router->getTimestamp()-$server->getTimestamp()]); }
    public function activeUsers(Request $r): \Ispluka\Core\Http\Response { $tenant=$this->tenant();$routerId=(int)$r->query('router_id',$r->input('router_id',0));$this->assertRouter($tenant,$routerId);return ApiResponse::success($this->gateway->activeUsers($routerId)); }
    private function tenant(): int { $tenant=(int)($this->auth->tenantId()??0);if($tenant<1)throw new RuntimeException('Invalid tenant context.');return $tenant; }
    private function assertRouter(int $tenantId,int $routerId): void { $s=$this->pdo->prepare("SELECT id FROM routers WHERE id=:id AND tenant_id=:t AND status='active'");$s->execute([':id'=>$routerId,':t'=>$tenantId]);if(!$s->fetchColumn())throw new RuntimeException('Router not found.'); }
    private function log(int $tenantId,?int $routerId,?int $hotspotUserId,string $action,string $status,array $details=[]): void { $s=$this->pdo->prepare('INSERT INTO hotspot_operation_logs(tenant_id,router_id,hotspot_user_id,actor_user_id,action,status,details) VALUES(:t,:r,:u,:a,:action,:status,:details)');$s->execute([':t'=>$tenantId,':r'=>$routerId,':u'=>$hotspotUserId,':a'=>$this->auth->userId(),':action'=>$action,':status'=>$status,':details'=>json_encode($details,JSON_THROW_ON_ERROR)]); }
}
