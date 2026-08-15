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
    public function delete(int $tenantId,int $routerId): void { $stmt=$this->database->pdo()->prepare('DELETE FROM routers WHERE tenant_id=:tenant_id AND id=:id');$stmt->execute([':tenant_id'=>$tenantId,':id'=>$routerId]); }
    public function markConnection(int $tenantId,int $routerId,bool $success,?string $error=null): void { $stmt=$this->database->pdo()->prepare('UPDATE routers SET last_seen_at = CASE WHEN :success THEN CURRENT_TIMESTAMP ELSE last_seen_at END, last_error = :error, updated_at = CURRENT_TIMESTAMP WHERE tenant_id = :tenant_id AND id = :id');$stmt->execute([':success'=>$success,':error'=>$success?null:mb_substr((string)$error,0,1000),':tenant_id'=>$tenantId,':id'=>$routerId]); }
}
