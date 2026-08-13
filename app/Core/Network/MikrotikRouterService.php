<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use Ispluka\Core\Database\Database;
use Ispluka\Core\Security\SecretBox;
use RuntimeException;
final class MikrotikRouterService {
 public function __construct(private readonly Database $db,private readonly SecretBox $secrets,private readonly MikrotikClientInterface $client) {}
 public function test(int $tenantId,int $routerId):array { $s=$this->db->pdo()->prepare('SELECT * FROM routers WHERE tenant_id=:t AND id=:id AND status=\'active\'');$s->execute([':t'=>$tenantId,':id'=>$routerId]);$r=$s->fetch();if(!$r)throw new RuntimeException('Router not found.');$r['password']=$this->secrets->decrypt($r['password_encrypted']);try{$this->client->connect($r);$result=$this->client->command('/system/identity/print');$this->client->disconnect();$this->db->pdo()->prepare('UPDATE routers SET last_seen_at=CURRENT_TIMESTAMP,last_error=NULL,updated_at=CURRENT_TIMESTAMP WHERE id=:id AND tenant_id=:t')->execute([':id'=>$routerId,':t'=>$tenantId]);return['ok'=>true,'identity'=>$result];}catch(\Throwable $e){try{$this->client->disconnect();}catch(\Throwable $ignore){}$this->db->pdo()->prepare('UPDATE routers SET last_error=:e,updated_at=CURRENT_TIMESTAMP WHERE id=:id AND tenant_id=:t')->execute([':e'=>substr($e->getMessage(),0,500),':id'=>$routerId,':t'=>$tenantId]);return['ok'=>false];}}
}
