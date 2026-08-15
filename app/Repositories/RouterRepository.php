<?php

declare(strict_types=1);

namespace Ispluka\Repositories;

use Ispluka\Core\Database\Database;
use PDO;

final class RouterRepository
{
    public function __construct(private readonly Database $database) {}
    public function find(int $tenantId,int $routerId): ?array { $stmt=$this->database->pdo()->prepare('SELECT * FROM routers WHERE tenant_id = :tenant_id AND id = :id LIMIT 1');$stmt->execute([':tenant_id'=>$tenantId,':id'=>$routerId]);$row=$stmt->fetch(PDO::FETCH_ASSOC);return $row?:null; }
    public function list(int $tenantId,int $limit=50,int $offset=0): array { $stmt=$this->database->pdo()->prepare('SELECT id,name,code,host,api_port,api_ssl_port,username,verify_ssl,status,last_seen_at,last_error,created_at,updated_at FROM routers WHERE tenant_id = :tenant_id ORDER BY id DESC LIMIT :limit OFFSET :offset');$stmt->bindValue(':tenant_id',$tenantId,PDO::PARAM_INT);$stmt->bindValue(':limit',min(max($limit,1),100),PDO::PARAM_INT);$stmt->bindValue(':offset',max($offset,0),PDO::PARAM_INT);$stmt->execute();return $stmt->fetchAll(PDO::FETCH_ASSOC); }
    public function create(int $tenantId,array $data): int { $stmt=$this->database->pdo()->prepare('INSERT INTO routers (tenant_id,name,code,host,api_port,api_ssl_port,username,encrypted_password,verify_ssl,status,metadata) VALUES (:tenant_id,:name,:code,:host,:api_port,:api_ssl_port,:username,:encrypted_password,:verify_ssl,:status,CAST(:metadata AS jsonb)) RETURNING id');$stmt->execute([':tenant_id'=>$tenantId,':name'=>$data['name'],':code'=>$data['code'],':host'=>$data['host'],':api_port'=>$data['api_port'],':api_ssl_port'=>$data['api_ssl_port'],':username'=>$data['username'],':encrypted_password'=>$data['encrypted_password'],':verify_ssl'=>$data['verify_ssl'],':status'=>$data['status'],':metadata'=>json_encode($data['metadata']??[],JSON_THROW_ON_ERROR)]);return (int)$stmt->fetchColumn(); }
    public function update(int $tenantId,int $routerId,array $data): void { $fields=['name=:name','code=:code','host=:host','api_port=:api_port','api_ssl_port=:api_ssl_port','username=:username','verify_ssl=:verify_ssl','updated_at=CURRENT_TIMESTAMP'];$params=[':tenant_id'=>$tenantId,':id'=>$routerId,':name'=>$data['name'],':code'=>$data['code'],':host'=>$data['host'],':api_port'=>$data['api_port'],':api_ssl_port'=>$data['api_ssl_port'],':username'=>$data['username'],':verify_ssl'=>$data['verify_ssl']];if(array_key_exists('encrypted_password',$data)){$fields[]='encrypted_password=:encrypted_password';$params[':encrypted_password']=$data['encrypted_password'];}if(array_key_exists('status',$data)){$fields[]='status=:status';$params[':status']=$data['status'];}if(array_key_exists('last_error',$data)){$fields[]='last_error=:last_error';$params[':last_error']=$data['last_error'];}$stmt=$this->database->pdo()->prepare('UPDATE routers SET '.implode(',',$fields).' WHERE tenant_id=:tenant_id AND id=:id');$stmt->execute($params); }
    public function delete(int $tenantId,int $routerId): void { $stmt=$this->database->pdo()->prepare('DELETE FROM routers WHERE tenant_id=:tenant_id AND id=:id');$stmt->execute([':tenant_id'=>$tenantId,':id'=>$routerId]); }
    public function markConnection(int $tenantId,int $routerId,bool $success,?string $error=null): void
    {
        if($success){
            $stmt=$this->database->pdo()->prepare('UPDATE routers SET status=:status,last_seen_at=CURRENT_TIMESTAMP,last_error=NULL,updated_at=CURRENT_TIMESTAMP WHERE tenant_id=:tenant_id AND id=:id');
            $stmt->execute([':status'=>'online',':tenant_id'=>$tenantId,':id'=>$routerId]);
            return;
        }
        $stmt=$this->database->pdo()->prepare('UPDATE routers SET status=:status,last_error=:error,updated_at=CURRENT_TIMESTAMP WHERE tenant_id=:tenant_id AND id=:id');
        $stmt->execute([':status'=>'offline',':error'=>mb_substr((string)$error,0,1000),':tenant_id'=>$tenantId,':id'=>$routerId]);
    }
}
