<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use Ispluka\Core\Database\Database;
use Ispluka\Core\Security\SecretBox;
use RuntimeException;
final class MikrotikAutomationService {
 public const SUSPEND_PROFILE='suspend';
 public function __construct(private readonly Database $db,private readonly SecretBox $secrets,private readonly MikrotikClientInterface $client) {}
 private function service(int $tenantId,int $serviceId):array { $s=$this->db->pdo()->prepare("SELECT cs.*,c.name customer_name,p.name package_name,p.download_kbps,p.upload_kbps,r.host,r.api_port,r.username router_username,r.password_encrypted FROM customer_services cs JOIN customers c ON c.id=cs.customer_id LEFT JOIN packages p ON p.id=cs.package_id LEFT JOIN routers r ON r.id=cs.router_id WHERE cs.tenant_id=:t AND cs.id=:s");$s->execute([':t'=>$tenantId,':s'=>$serviceId]);$r=$s->fetch();if(!$r||!$r['router_id'])throw new RuntimeException('Service or router not found.');return$r; }
 private function router(array $r):array{return['host'=>$r['host'],'api_port'=>$r['api_port'],'username'=>$r['router_username'],'password'=>$this->secrets->decrypt($r['password_encrypted'])];}
 public function provision(int $tenantId,int $serviceId):array { $r=$this->service($tenantId,$serviceId);$router=$this->router($r);try{$this->client->connect($router);$cmd=$r['connection_type']==='hotspot'?'/ip/hotspot/user/add':'/ppp/secret/add';$args=['name'=>$r['username'],'password'=>$r['password_encrypted']??''];$result=$this->client->command($cmd,$args);return['ok'=>true,'result'=>$result];}catch(\Throwable $e){throw new RuntimeException('MikroTik provisioning failed.',0,$e);}finally{try{$this->client->disconnect();}catch(\Throwable $ignore){}}}
 public function execute(int $tenantId,int $serviceId,string $action):array { $action=strtolower(trim($action));if(!in_array($action,['enable','disable','suspend'],true))throw new RuntimeException('Unsupported MikroTik action.');$r=$this->service($tenantId,$serviceId);$router=$this->router($r);$this->client->connect($router);try{if($r['connection_type']==='hotspot'){ $cmd='/ip/hotspot/user/set';$args=['name'=>$r['username']];if($action==='suspend'){$args['profile']=self::SUSPEND_PROFILE;}elseif($action==='disable'){$args['disabled']='yes';}else{$args['disabled']='no';} }else{ $cmd='/ppp/secret/set';$args=['name'=>$r['username']];if($action==='suspend'){$args['profile']=self::SUSPEND_PROFILE;$args['disabled']='no';}elseif($action==='disable'){$args['disabled']='yes';}else{$args['disabled']='no';} }$result=$this->client->command($cmd,$args);return['ok'=>true,'action'=>$action,'requested'=>$args,'result'=>$result];}finally{$this->client->disconnect();}}
 public function suspend(int $tenantId,int $serviceId):void {$this->execute($tenantId,$serviceId,'suspend');}
 public function disable(int $tenantId,int $serviceId):void {$this->execute($tenantId,$serviceId,'disable');}
 public function enable(int $tenantId,int $serviceId):void {$this->execute($tenantId,$serviceId,'enable');}
 public function restore(int $tenantId,int $serviceId):void {$this->enable($tenantId,$serviceId);}
}
