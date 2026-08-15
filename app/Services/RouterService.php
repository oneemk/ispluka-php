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
    public function __construct(
        private readonly RouterRepository $routers,
        private readonly SecretBox $secrets,
        private readonly MikrotikClientInterface $client,
    ) {}

    public function list(int $tenantId): array
    { return $this->routers->list($tenantId); }

    public function create(int $tenantId, array $data): int
    {
        $host=trim((string)($data['host']??''));$name=trim((string)($data['name']??''));$code=trim((string)($data['code']??''));$username=trim((string)($data['username']??''));$password=(string)($data['password']??'');$apiPort=(int)($data['api_port']??8728);
        if($tenantId<=0||$name===''||$code===''||$host===''||$username===''||$password==='') throw new InvalidArgumentException('Router name, code, host, username and password are required.');
        if($apiPort<1||$apiPort>65535) throw new InvalidArgumentException('Invalid RouterOS API port.');
        $sslPort=isset($data['api_ssl_port'])&&$data['api_ssl_port']!==''?(int)$data['api_ssl_port']:null;
        if($sslPort!==null&&($sslPort<1||$sslPort>65535)) throw new InvalidArgumentException('Invalid RouterOS SSL API port.');
        return $this->routers->create($tenantId,['name'=>$name,'code'=>$code,'host'=>$host,'api_port'=>$apiPort,'api_ssl_port'=>$sslPort,'username'=>$username,'encrypted_password'=>$this->secrets->encrypt($password),'verify_ssl'=>(bool)($data['verify_ssl']??true),'status'=>'active','metadata'=>$data['metadata']??[]]);
    }

    public function delete(int $tenantId,int $routerId): void
    { if($routerId<=0||!$this->routers->find($tenantId,$routerId)) throw new RuntimeException('Router not found.'); $this->routers->delete($tenantId,$routerId); }

    public function testConnection(int $tenantId,int $routerId): array
    {
        $router=$this->routers->find($tenantId,$routerId);if($router===null) throw new RuntimeException('Router not found.');
        try {
            $this->client->connect($this->routerConfig($router));
            $identity=$this->client->command('/system/identity/print');
            $this->routers->markConnection($tenantId,$routerId,true);
            return ['ok'=>true,'identity'=>$identity[0]['name']??null];
        } catch(\Throwable $e) {
            $this->routers->markConnection($tenantId,$routerId,false,$e->getMessage());
            return ['ok'=>false,'error'=>'Router connection failed.'];
        } finally { try{$this->client->disconnect();}catch(\Throwable $ignore){} }
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
        $router=$this->routers->find($tenantId,$routerId);if($router===null) throw new RuntimeException('Router not found.');
        try{$this->client->connect($this->routerConfig($router));$rows=$this->client->command($findCommand,['?name'=>$username]);if(!empty($rows[0]['.id']))$this->client->command($setCommand,['.id'=>$rows[0]['.id']]+$data);else $this->client->command($addCommand,$data);$this->routers->markConnection($tenantId,$routerId,true);}
        catch(\Throwable $e){$this->routers->markConnection($tenantId,$routerId,false,$e->getMessage());throw new RuntimeException('Router provisioning failed.',0,$e);}finally{try{$this->client->disconnect();}catch(\Throwable $ignore){}}
    }

    private function toggleUser(int $tenantId,int $routerId,string $findCommand,string $actionCommand,string $username): void
    {
        $router=$this->routers->find($tenantId,$routerId);if($router===null) throw new RuntimeException('Router not found.');
        try{$this->client->connect($this->routerConfig($router));$rows=$this->client->command($findCommand,['?name'=>$username]);if(empty($rows[0]['.id']))throw new RuntimeException('Service account not found.');$this->client->command($actionCommand,['.id'=>$rows[0]['.id']]);$this->routers->markConnection($tenantId,$routerId,true);}
        catch(\Throwable $e){$this->routers->markConnection($tenantId,$routerId,false,$e->getMessage());throw new RuntimeException('Router service state change failed.',0,$e);}finally{try{$this->client->disconnect();}catch(\Throwable $ignore){}}
    }

    private function routerConfig(array $router): array
    {
        $port=!empty($router['api_ssl_port'])?(int)$router['api_ssl_port']:(int)($router['api_port']??8728);
        if($port<1||$port>65535) throw new RuntimeException('Invalid RouterOS API port.');
        return ['host'=>(string)$router['host'],'api_port'=>$port,'username'=>(string)$router['username'],'password'=>$this->secrets->decrypt((string)$router['encrypted_password']),'verify_ssl'=>(bool)($router['verify_ssl']??true)];
    }
}
