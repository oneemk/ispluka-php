<?php

declare(strict_types=1);

namespace Ispluka\Services;

use Ispluka\Core\Network\MikrotikClientInterface;
use Ispluka\Core\Security\SecretBox;
use Ispluka\Repositories\RouterRepository;
use InvalidArgumentException;
use RuntimeException;

final class RouterService
{
    public function __construct(private readonly RouterRepository $routers,private readonly SecretBox $secrets,private readonly MikrotikClientInterface $client) {}
    public function list(int $tenantId): array { return $this->routers->list($tenantId); }
    public function create(int $tenantId,array $data): int
    {
        $host=trim((string)($data['host']??''));$name=trim((string)($data['name']??''));$code=trim((string)($data['code']??''));$username=trim((string)($data['username']??''));$password=(string)($data['password']??'');$apiPort=(int)($data['api_port']??8728);
        if($tenantId<=0||$name===''||$code===''||$host===''||$username===''||$password==='') throw new InvalidArgumentException('Router name, code, host, username and password are required.');
        $this->validatePorts($apiPort,$data['api_ssl_port']??null);$sslPort=isset($data['api_ssl_port'])&&$data['api_ssl_port']!==''?(int)$data['api_ssl_port']:null;
        return $this->routers->create($tenantId,['name'=>$name,'code'=>$code,'host'=>$host,'api_port'=>$apiPort,'api_ssl_port'=>$sslPort,'username'=>$username,'encrypted_password'=>$this->secrets->encrypt($password),'verify_ssl'=>(bool)($data['verify_ssl']??true),'status'=>'unknown','metadata'=>$data['metadata']??[]]);
    }
    public function update(int $tenantId,int $routerId,array $data): void
    {
        $router=$this->routers->find($tenantId,$routerId);if($router===null) throw new RuntimeException('Router not found.');
        $name=trim((string)($data['name']??''));$code=trim((string)($data['code']??''));$host=trim((string)($data['host']??''));$username=trim((string)($data['username']??''));$apiPort=(int)($data['api_port']??8728);$ssl=$data['api_ssl_port']??null;
        if($name===''||$code===''||$host===''||$username==='') throw new InvalidArgumentException('Router name, code, host and username are required.');
        $this->validatePorts($apiPort,$ssl);$sslPort=($ssl!==null&&$ssl!=='')?(int)$ssl:null;$payload=['name'=>$name,'code'=>$code,'host'=>$host,'api_port'=>$apiPort,'api_ssl_port'=>$sslPort,'username'=>$username,'verify_ssl'=>(bool)($data['verify_ssl']??false),'status'=>'unknown','last_error'=>null];$password=(string)($data['password']??'');if($password!=='')$payload['encrypted_password']=$this->secrets->encrypt($password);$this->routers->update($tenantId,$routerId,$payload);
    }
    public function delete(int $tenantId,int $routerId): void { if($routerId<=0||!$this->routers->find($tenantId,$routerId)) throw new RuntimeException('Router not found.');$this->routers->delete($tenantId,$routerId); }
    public function status(int $tenantId,int $routerId): array { return $this->testConnection($tenantId,$routerId); }
    public function testConnection(int $tenantId,int $routerId): array
    {
        if($routerId<=0) throw new InvalidArgumentException('Router ID is required.');
        $router=$tenantId>0?$this->routers->find($tenantId,$routerId):$this->routers->findAny($routerId);
        if($router===null) throw new RuntimeException('Router not found.');
        $actualTenantId=(int)$router['tenant_id'];
        try{$this->client->connect($this->routerConfig($router));$identity=$this->client->command('/system/identity/print');$this->routers->markConnection($actualTenantId,$routerId,true);return ['ok'=>true,'status'=>'online','identity'=>$identity[0]['name']??null];}
        catch(\Throwable $e){$message=trim($e->getMessage())!==''?$e->getMessage():'Unknown MikroTik connection error.';$this->routers->markConnection($actualTenantId,$routerId,false,$message);return ['ok'=>false,'status'=>'offline','error'=>$message];}
        finally{try{$this->client->disconnect();}catch(\Throwable $ignore){}}
    }
    public function provisionPppoe(int $tenantId,int $routerId,string $username,string $password,string $profile): void { $this->provisionUser($tenantId,$routerId,'/ppp/secret/print','/ppp/secret/add','/ppp/secret/set',$username,['name'=>$username,'password'=>$password,'service'=>'pppoe','profile'=>$profile]); }
    public function suspendPppoe(int $tenantId,int $routerId,string $username): void {$this->toggleUser($tenantId,$routerId,'/ppp/secret/print','/ppp/secret/disable',$username);}
    public function restorePppoe(int $tenantId,int $routerId,string $username): void {$this->toggleUser($tenantId,$routerId,'/ppp/secret/print','/ppp/secret/enable',$username);}
    public function provisionHotspot(int $tenantId,int $routerId,string $username,string $password,string $profile): void {$this->provisionUser($tenantId,$routerId,'/ip/hotspot/user/print','/ip/hotspot/user/add','/ip/hotspot/user/set',$username,['name'=>$username,'password'=>$password,'profile'=>$profile]);}
    public function suspendHotspot(int $tenantId,int $routerId,string $username): void {$this->toggleUser($tenantId,$routerId,'/ip/hotspot/user/print','/ip/hotspot/user/disable',$username);}
    public function restoreHotspot(int $tenantId,int $routerId,string $username): void {$this->toggleUser($tenantId,$routerId,'/ip/hotspot/user/print','/ip/hotspot/user/enable',$username);}
    private function provisionUser(int $tenantId,int $routerId,string $findCommand,string $addCommand,string $setCommand,string $username,array $data): void {$router=$this->routers->find($tenantId,$routerId);if($router===null)throw new RuntimeException('Router not found.');try{$this->client->connect($this->routerConfig($router));$rows=$this->client->command($findCommand,['?name'=>$username]);if(!empty($rows[0]['.id']))$this->client->command($setCommand,['.id'=>$rows[0]['.id']]+$data);else $this->client->command($addCommand,$data);$this->routers->markConnection($tenantId,$routerId,true);}catch(\Throwable $e){$this->routers->markConnection($tenantId,$routerId,false,$e->getMessage());throw new RuntimeException('Router provisioning failed.',0,$e);}finally{try{$this->client->disconnect();}catch(\Throwable $ignore){}}}
    private function toggleUser(int $tenantId,int $routerId,string $findCommand,string $actionCommand,string $username): void {$router=$this->routers->find($tenantId,$routerId);if($router===null)throw new RuntimeException('Router not found.');try{$this->client->connect($this->routerConfig($router));$rows=$this->client->command($findCommand,['?name'=>$username]);if(empty($rows[0]['.id']))throw new RuntimeException('Service account not found.');$this->client->command($actionCommand,['.id'=>$rows[0]['.id']]);$this->routers->markConnection($tenantId,$routerId,true);}catch(\Throwable $e){$this->routers->markConnection($tenantId,$routerId,false,$e->getMessage());throw new RuntimeException('Router service state change failed.',0,$e);}finally{try{$this->client->disconnect();}catch(\Throwable $ignore){}}}
    private function validatePorts(int $apiPort,mixed $ssl): void {if($apiPort<1||$apiPort>65535)throw new InvalidArgumentException('Invalid RouterOS API port.');if($ssl!==null&&$ssl!==''&&((int)$ssl<1||(int)$ssl>65535))throw new InvalidArgumentException('Invalid RouterOS SSL API port.');}
    private function routerConfig(array $router): array {$port=!empty($router['api_ssl_port'])?(int)$router['api_ssl_port']:(int)($router['api_port']??8728);if($port<1||$port>65535)throw new RuntimeException('Invalid RouterOS API port.');return ['host'=>(string)$router['host'],'api_port'=>$port,'username'=>(string)$router['username'],'password'=>$this->secrets->decrypt((string)$router['encrypted_password']),'verify_ssl'=>(bool)($router['verify_ssl']??true)];}
}
