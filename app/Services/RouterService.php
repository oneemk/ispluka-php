<?php

declare(strict_types=1);

namespace Ispluka\Services;

use Ispluka\Core\Network\RouterOsClient;
use Ispluka\Core\Security\SecretBox;
use Ispluka\Repositories\RouterRepository;
use InvalidArgumentException;
use RuntimeException;

final class RouterService
{
    public function __construct(private readonly RouterRepository $routers, private readonly SecretBox $secrets) {}

    public function list(int $tenantId): array
    { return $this->routers->list($tenantId); }

    public function create(int $tenantId, array $data): int
    {
        $host=trim((string)($data['host']??''));$name=trim((string)($data['name']??''));$code=trim((string)($data['code']??''));$username=trim((string)($data['username']??''));$password=(string)($data['password']??'');$apiPort=(int)($data['api_port']??8728);
        if($tenantId<=0||$name===''||$code===''||$host===''||$username===''||$password==='') throw new InvalidArgumentException('Router name, code, host, username and password are required.');
        if($apiPort<1||$apiPort>65535) throw new InvalidArgumentException('Invalid RouterOS API port.');
        return $this->routers->create($tenantId,['name'=>$name,'code'=>$code,'host'=>$host,'api_port'=>$apiPort,'api_ssl_port'=>isset($data['api_ssl_port'])?(int)$data['api_ssl_port']:null,'username'=>$username,'encrypted_password'=>$this->secrets->encrypt($password),'verify_ssl'=>(bool)($data['verify_ssl']??true),'status'=>'active','metadata'=>$data['metadata']??[]]);
    }

    public function delete(int $tenantId,int $routerId): void
    { if($routerId<=0||!$this->routers->find($tenantId,$routerId)) throw new RuntimeException('Router not found.'); $this->routers->delete($tenantId,$routerId); }

    public function testConnection(int $tenantId,int $routerId): array
    {
        $router=$this->routers->find($tenantId,$routerId);if($router===null) throw new RuntimeException('Router not found.');$client=$this->client($router);
        try{$identity=$client->command('/system/identity/print');$this->routers->markConnection($tenantId,$routerId,true);return ['ok'=>true,'identity'=>$identity[0]['=name']??null];}
        catch(\Throwable $e){$this->routers->markConnection($tenantId,$routerId,false,$e->getMessage());return ['ok'=>false,'error'=>'Router connection failed.'];}
        finally{$client->disconnect();}
    }

    public function provisionPppoe(int $tenantId,int $routerId,string $username,string $password,string $profile): void
    { $this->provisionUser($tenantId,$routerId,'/ppp/secret/print','/ppp/secret/add','/ppp/secret/set',$username,['name'=>$username,'password'=>$password,'service'=>'pppoe','profile'=>$profile]); }
    public function suspendPppoe(int $tenantId,int $routerId,string $username): void { $this->toggleUser($tenantId,$routerId,'/ppp/secret/print','/ppp/secret/disable',$username); }
    public function restorePppoe(int $tenantId,int $routerId,string $username): void { $this->toggleUser($tenantId,$routerId,'/ppp/secret/print','/ppp/secret/enable',$username); }
    public function provisionHotspot(int $tenantId,int $routerId,string $username,string $password,string $profile): void
    { $this->provisionUser($tenantId,$routerId,'/ip/hotspot/user/print','/ip/hotspot/user/add','/ip/hotspot/user/set',$username,['name'=>$username,'password'=>$password,'profile'=>$profile]); }
    public function suspendHotspot(int $tenantId,int $routerId,string $username): void { $this->toggleUser($tenantId,$routerId,'/ip/hotspot/user/print','/ip/hotspot/user/disable',$username); }
    public function restoreHotspot(int $tenantId,int $routerId,string $username): void { $this->toggleUser($tenantId,$routerId,'/ip/hotspot/user/print','/ip/hotspot/user/enable',$username); }

    private function provisionUser(int $tenantId,int $routerId,string $findCommand,string $addCommand,string $setCommand,string $username,array $data): void
    {
        $router=$this->routers->find($tenantId,$routerId);if($router===null) throw new RuntimeException('Router not found.');$client=$this->client($router);
        try{$rows=$client->command($findCommand,['?name'=>$username]);if(!empty($rows[0]['=.id']))$client->command($setCommand,array_merge(['numbers'=>$rows[0]['=.id']],$data));else $client->command($addCommand,$data);$this->routers->markConnection($tenantId,$routerId,true);}
        catch(\Throwable $e){$this->routers->markConnection($tenantId,$routerId,false,$e->getMessage());throw new RuntimeException('Router provisioning failed.',0,$e);}finally{$client->disconnect();}
    }
    private function toggleUser(int $tenantId,int $routerId,string $findCommand,string $actionCommand,string $username): void
    {
        $router=$this->routers->find($tenantId,$routerId);if($router===null) throw new RuntimeException('Router not found.');$client=$this->client($router);
        try{$rows=$client->command($findCommand,['?name'=>$username]);if(empty($rows[0]['=.id']))throw new RuntimeException('Service account not found.');$client->command($actionCommand,['numbers'=>$rows[0]['=.id']]);$this->routers->markConnection($tenantId,$routerId,true);}
        catch(\Throwable $e){$this->routers->markConnection($tenantId,$routerId,false,$e->getMessage());throw new RuntimeException('Router service state change failed.',0,$e);}finally{$client->disconnect();}
    }
    private function client(array $router): RouterOsClient
    { $port=!empty($router['api_ssl_port'])?(int)$router['api_ssl_port']:(int)$router['api_port'];$client=new RouterOsClient((string)$router['host'],$port,5,(bool)$router['verify_ssl']);$client->connect((string)$router['username'],$this->secrets->decrypt((string)$router['encrypted_password']));return $client; }
}
