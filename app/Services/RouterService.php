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
    public function __construct(private readonly RouterRepository $routers, private readonly SecretBox $secrets, private readonly MikrotikClientInterface $client) {}
    public function list(int $tenantId): array { return $this->routers->list($tenantId); }

    public function create(int $tenantId, array $data): int
    {
        $name=trim((string)($data['name']??''));$code=trim((string)($data['code']??''));$host=trim((string)($data['host']??''));$username=trim((string)($data['username']??''));$password=(string)($data['password']??'');$method=strtolower(trim((string)($data['connection_method']??'api')));
        if($tenantId<=0||$name===''||$code===''||$host===''||$username===''||$password==='')throw new InvalidArgumentException('Router name, code, host, username and password are required.');
        $ports=$this->validatePorts($method,$data['api_port']??null,$data['api_ssl_port']??null,$data['ssh_port']??null);
        return $this->routers->create($tenantId,['name'=>$name,'code'=>$code,'host'=>$host,'connection_method'=>$method,'api_port'=>$ports['api_port'],'api_ssl_port'=>$ports['api_ssl_port'],'ssh_port'=>$ports['ssh_port'],'username'=>$username,'encrypted_password'=>$this->secrets->encrypt($password),'verify_ssl'=>(bool)($data['verify_ssl']??true),'status'=>'unknown','metadata'=>$data['metadata']??[]]);
    }

    public function update(int $tenantId,int $routerId,array $data): void
    {
        $router=$this->routers->find($tenantId,$routerId);if($router===null)throw new RuntimeException('Router not found.');
        $name=trim((string)($data['name']??''));$code=trim((string)($data['code']??''));$host=trim((string)($data['host']??''));$username=trim((string)($data['username']??''));$method=strtolower(trim((string)($data['connection_method']??($router['connection_method']??'api'))));
        if($name===''||$code===''||$host===''||$username==='')throw new InvalidArgumentException('Router name, code, host and username are required.');
        $ports=$this->validatePorts($method,$data['api_port']??$router['api_port']??null,$data['api_ssl_port']??$router['api_ssl_port']??null,$data['ssh_port']??$router['ssh_port']??null);
        $verifySsl=array_key_exists('verify_ssl',$data)?(bool)$data['verify_ssl']:(bool)($router['verify_ssl']??true);
        $payload=['name'=>$name,'code'=>$code,'host'=>$host,'connection_method'=>$method,'api_port'=>$ports['api_port'],'api_ssl_port'=>$ports['api_ssl_port'],'ssh_port'=>$ports['ssh_port'],'username'=>$username,'verify_ssl'=>$verifySsl,'status'=>'unknown','last_error'=>null];
        $password=(string)($data['password']??'');if($password!=='')$payload['encrypted_password']=$this->secrets->encrypt($password);$this->routers->update($tenantId,$routerId,$payload);
    }

    public function delete(int $tenantId,int $routerId): void { if($routerId<=0||!$this->routers->find($tenantId,$routerId))throw new RuntimeException('Router not found.');$this->routers->delete($tenantId,$routerId); }
    public function status(int $tenantId,int $routerId): array{return $this->testConnection($tenantId,$routerId);}

    public function testConnection(int $tenantId,int $routerId): array
    {
        if($routerId<=0)throw new InvalidArgumentException('Router ID is required.');
        $router=$tenantId>0?$this->routers->find($tenantId,$routerId):$this->routers->findAny($routerId);if($router===null)throw new RuntimeException('Router not found.');
        $actualTenantId=(int)$router['tenant_id'];$method=strtolower((string)($router['connection_method']??'api'));
        $targetPort=$method==='ssh'?(int)($router['ssh_port']??22):(!empty($router['api_ssl_port'])?(int)$router['api_ssl_port']:(int)($router['api_port']??8728));
        try{
            $this->client->connect($this->routerConfig($router));
            $identity=$this->client->command('/system/identity/print');
            $resource=$this->client->command('/system/resource/print',['detail'=>true]);
            $health=$this->normalizeHealth($resource[0]??[]);
            $this->routers->markConnection($actualTenantId,$routerId,true);
            return ['ok'=>true,'status'=>'online','connection_method'=>$method,'host'=>(string)$router['host'],'port'=>$targetPort,'identity'=>$identity[0]['name']??null,'health'=>$health];
        }
        catch(\Throwable $e){$message=trim($e->getMessage())!==''?$e->getMessage():'Unknown MikroTik connection error.';$this->routers->markConnection($actualTenantId,$routerId,false,$message);return ['ok'=>false,'status'=>'offline','connection_method'=>$method,'host'=>(string)$router['host'],'port'=>$targetPort,'error'=>$message];}
        finally{try{$this->client->disconnect();}catch(\Throwable $ignore){}}
    }

    public function provisionPppoe(int $tenantId,int $routerId,string $username,string $password,string $profile):void{$this->provisionUser($tenantId,$routerId,'/ppp/secret/print','/ppp/secret/add','/ppp/secret/set',$username,['name'=>$username,'password'=>$password,'service'=>'pppoe','profile'=>$profile]);}
    public function suspendPppoe(int $tenantId,int $routerId,string $username):void{$this->toggleUser($tenantId,$routerId,'/ppp/secret/print','/ppp/secret/disable',$username);}
    public function restorePppoe(int $tenantId,int $routerId,string $username):void{$this->toggleUser($tenantId,$routerId,'/ppp/secret/print','/ppp/secret/enable',$username);}
    public function provisionHotspot(int $tenantId,int $routerId,string $username,string $password,string $profile):void{$this->provisionUser($tenantId,$routerId,'/ip/hotspot/user/print','/ip/hotspot/user/add','/ip/hotspot/user/set',$username,['name'=>$username,'password'=>$password,'profile'=>$profile]);}
    public function suspendHotspot(int $tenantId,int $routerId,string $username):void{$this->toggleUser($tenantId,$routerId,'/ip/hotspot/user/print','/ip/hotspot/user/disable',$username);}
    public function restoreHotspot(int $tenantId,int $routerId,string $username):void{$this->toggleUser($tenantId,$routerId,'/ip/hotspot/user/print','/ip/hotspot/user/enable',$username);}

    private function provisionUser(int $tenantId,int $routerId,string $findCommand,string $addCommand,string $setCommand,string $username,array $data):void{$router=$this->routers->find($tenantId,$routerId);if($router===null)throw new RuntimeException('Router not found.');try{$this->client->connect($this->routerConfig($router));$rows=$this->client->command($findCommand,['?name'=>$username]);if(!empty($rows[0]['.id']))$this->client->command($setCommand,['.id'=>$rows[0]['.id']]+$data);else $this->client->command($addCommand,$data);$this->routers->markConnection($tenantId,$routerId,true);}catch(\Throwable $e){$this->routers->markConnection($tenantId,$routerId,false,$e->getMessage());throw new RuntimeException('Router provisioning failed.',0,$e);}finally{try{$this->client->disconnect();}catch(\Throwable $ignore){}}}
    private function toggleUser(int $tenantId,int $routerId,string $findCommand,string $actionCommand,string $username):void{$router=$this->routers->find($tenantId,$routerId);if($router===null)throw new RuntimeException('Router not found.');try{$this->client->connect($this->routerConfig($router));$rows=$this->client->command($findCommand,['?name'=>$username]);if(empty($rows[0]['.id']))throw new RuntimeException('Service account not found.');$this->client->command($actionCommand,['.id'=>$rows[0]['.id']]);$this->routers->markConnection($tenantId,$routerId,true);}catch(\Throwable $e){$this->routers->markConnection($tenantId,$routerId,false,$e->getMessage());throw new RuntimeException('Router service state change failed.',0,$e);}finally{try{$this->client->disconnect();}catch(\Throwable $ignore){}}}

    private function validatePorts(string $method,mixed $api,mixed $ssl,mixed $ssh):array
    {
        if(!in_array($method,['api','ssh'],true))throw new InvalidArgumentException('Connection method must be API or SSH.');
        $apiPort=($api!==null&&$api!=='')?(int)$api:8728;$sslPort=($ssl!==null&&$ssl!=='')?(int)$ssl:null;$sshPort=($ssh!==null&&$ssh!=='')?(int)$ssh:22;
        if($method==='api'&&($apiPort<1||$apiPort>65535))throw new InvalidArgumentException('Invalid RouterOS API port.');
        if($method==='ssh'&&($sshPort<1||$sshPort>65535))throw new InvalidArgumentException('Invalid RouterOS SSH port.');
        if($sslPort!==null&&($sslPort<1||$sslPort>65535))throw new InvalidArgumentException('Invalid RouterOS SSL API port.');
        return ['api_port'=>$apiPort,'api_ssl_port'=>$sslPort,'ssh_port'=>$sshPort];
    }

    private function routerConfig(array $router):array
    {
        $method=strtolower((string)($router['connection_method']??'api'));
        if($method==='ssh')return ['host'=>(string)$router['host'],'connection_method'=>'ssh','ssh_port'=>(int)($router['ssh_port']??22),'username'=>(string)$router['username'],'password'=>$this->secrets->decrypt((string)$router['encrypted_password'])];
        $port=!empty($router['api_ssl_port'])?(int)$router['api_ssl_port']:(int)($router['api_port']??8728);if($port<1||$port>65535)throw new RuntimeException('Invalid RouterOS API port.');
        return ['host'=>(string)$router['host'],'connection_method'=>'api','api_port'=>$port,'username'=>(string)$router['username'],'password'=>$this->secrets->decrypt((string)$router['encrypted_password']),'verify_ssl'=>(bool)($router['verify_ssl']??true),'api_ssl'=>!empty($router['api_ssl_port'])];
    }

    private function normalizeHealth(array $resource): array
    {
        $number = static function(mixed $value): ?float { if($value===null||$value==='')return null; if(!is_numeric($value))return null; return (float)$value; };
        $memoryTotal=$number($resource['total-memory']??$resource['total_memory']??null);
        $memoryFree=$number($resource['free-memory']??$resource['free_memory']??null);
        $memoryUsed=($memoryTotal!==null&&$memoryFree!==null)?max(0,$memoryTotal-$memoryFree):null;
        $memoryPct=($memoryTotal!==null&&$memoryTotal>0&&$memoryUsed!==null)?round(($memoryUsed/$memoryTotal)*100,1):null;
        return [
            'uptime'=>(string)($resource['uptime']??''),
            'version'=>(string)($resource['version']??''),
            'board_name'=>(string)($resource['board-name']??$resource['board_name']??''),
            'architecture'=>(string)($resource['architecture-name']??$resource['architecture_name']??''),
            'cpu_load'=>$number($resource['cpu-load']??$resource['cpu_load']??null),
            'total_memory'=>$memoryTotal,
            'free_memory'=>$memoryFree,
            'used_memory'=>$memoryUsed,
            'memory_used_percent'=>$memoryPct,
        ];
    }
}
