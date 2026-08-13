<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use Ispluka\Core\Database\Database;
use Ispluka\Core\Security\SecretBox;
use RuntimeException;
final class MikrotikAutomationService {
 public function __construct(private readonly Database $db,private readonly SecretBox $secrets,private readonly MikrotikClientInterface $client) {}
 private function service(int $tenantId,int $serviceId):array { $s=$this->db->pdo()->prepare("SELECT cs.*,c.name customer_name,p.name package_name,p.download_kbps,p.upload_kbps,r.host,r.api_port,r.username router_username,r.password_encrypted FROM customer_services cs JOIN customers c ON c.id=cs.customer_id LEFT JOIN packages p ON p.id=cs.package_id LEFT JOIN routers r ON r.id=cs.router_id WHERE cs.tenant_id=:t AND cs.id=:s");$s->execute([':t'=>$tenantId,':s'=>$serviceId]);$r=$s->fetch();if(!$r||!$r['router_id'])throw new RuntimeException('Service or router not found.');return$r; }
 public function provision(int $tenantId,int $serviceId):array { $r=$this->service($tenantId,$serviceId);$router=['host'=>$r['host'],'api_port'=>$r['api_port'],'username'=>$r['router_username'],'password'=>$this->secrets->decrypt($r['password_encrypted'])];try{$this->client->connect($router);$cmd=$r['connection_type']==='hotspot'?'/ip/hotspot/user/add':'/ppp/secret/add';$args=['name'=>$r['username'],'password'=>$r['password_encrypted']??''];$result=$this->client->command($cmd,$args);$this->client->disconnect();return['ok'=>true,'result'=>$result];}catch(\Throwable $e){try{$this->client->disconnect();}catch(\Throwable $ignore){}throw new RuntimeException('MikroTik provisioning failed.',0,$e);}}
 public function suspend(int $tenantId,int $serviceId):void { $r=$this->service($tenantId,$serviceId);$router=['host'=>$r['host'],'api_port'=>$r['api_port'],'username'=>$r['router_username'],'password'=>$this->secrets->decrypt($r['password_encrypted'])];$this->client->connect($router);try{$cmd=$r['connection_type']==='hotspot'?'/ip/hotspot/user/set':'/ppp/secret/set';$this->client->command($cmd,['name'=>$r['username'],'disabled'=>'yes']);}finally{$this->client->disconnect();}}
 public function restore(int $tenantId,int $serviceId):void { $r=$this->service($tenantId,$serviceId);$router=['host'=>$r['host'],'api_port'=>$r['api_port'],'username'=>$r['router_username'],'password'=>$this->secrets->decrypt($r['password_encrypted'])];$this->client->connect($router);try{$cmd=$r['connection_type']==='hotspot'?'/ip/hotspot/user/set':'/ppp/secret/set';$this->client->command($cmd,['name'=>$r['username'],'disabled'=>'no']);}finally{$this->client->disconnect();}}
}
