<?php

declare(strict_types=1);
namespace Ispluka\Core\Hotspot;
use Ispluka\Core\Database\Database;
use RuntimeException;
final class HotspotValidityService {
 public function __construct(private readonly Database $db) {}
 public function activate(int $tenantId,int $userId,?\DateTimeImmutable $now=null):array { $now??=new \DateTimeImmutable('now',new \DateTimeZone('UTC'));$pdo=$this->db->pdo();$pdo->beginTransaction();try{$s=$pdo->prepare("SELECT u.id,u.status,u.activated_at,u.expires_at,p.validity_seconds FROM hotspot_users u JOIN hotspot_profiles p ON p.id=u.profile_id AND p.tenant_id=u.tenant_id WHERE u.tenant_id=:t AND u.id=:i FOR UPDATE");$s->execute([':t'=>$tenantId,':i'=>$userId]);$row=$s->fetch();if(!$row)throw new RuntimeException('Hotspot user not found.');if($row['status']==='disabled')throw new RuntimeException('Hotspot user is disabled.');if($row['status']==='expired')return$row;if($row['activated_at']!==null)return$row;$expires=$now->modify('+'.((int)$row['validity_seconds']).' seconds');$u=$pdo->prepare("UPDATE hotspot_users SET status='active',activated_at=:a,expires_at=:e,updated_at=CURRENT_TIMESTAMP WHERE tenant_id=:t AND id=:i AND activated_at IS NULL");$u->execute([':a'=>$now->format('Y-m-d H:i:sP'),':e'=>$expires->format('Y-m-d H:i:sP'),':t'=>$tenantId,':i'=>$userId]);$pdo->commit();return['id'=>$userId,'status'=>'active','activated_at'=>$now->format(DATE_ATOM),'expires_at'=>$expires->format(DATE_ATOM)];}catch(\Throwable $e){$pdo->rollBack();throw$e;}}
 public function expireDue(int $tenantId,?\DateTimeImmutable $now=null):int{$now??=new \DateTimeImmutable('now',new \DateTimeZone('UTC'));$s=$this->db->pdo()->prepare("UPDATE hotspot_users SET status='expired',updated_at=CURRENT_TIMESTAMP WHERE tenant_id=:t AND status='active' AND expires_at<=:n");$s->execute([':t'=>$tenantId,':n'=>$now->format('Y-m-d H:i:sP')]);return$s->rowCount();}
}
