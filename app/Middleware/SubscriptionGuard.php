<?php

declare(strict_types=1);
namespace Ispluka\Middleware;
use Ispluka\Core\Auth\AuthManager;use Ispluka\Core\Database\Database;use Ispluka\Core\Http\Request;use Ispluka\Core\Http\Response;
final class SubscriptionGuard{
public function __construct(private readonly Database $database,private readonly AuthManager $auth){}
public function __invoke(Request $request,callable $next):Response{$uid=$this->auth->userId();if($uid===null)return Response::redirect('/login');if($this->isMasterAdmin($uid)||in_array($request->path(),['/subscription','/logout'],true))return $next($request);$status=$this->status($uid,$this->auth->roleCode());if($status)return $next($request);return Response::redirect('/subscription');}
private function status(int $uid,?string $role):bool{$pdo=$this->database->pdo();if($role==='reseller'){$s=$pdo->prepare('SELECT status,ends_at FROM user_subscriptions WHERE user_id=:uid ORDER BY id DESC LIMIT 1');$s->execute(['uid'=>$uid]);$r=$s->fetch();if(is_array($r))return $this->valid((string)($r['status']??''),$r['ends_at']??null);} $s=$pdo->prepare('SELECT status,ends_at FROM tenant_subscriptions WHERE tenant_id=(SELECT tenant_id FROM users WHERE id=:uid) ORDER BY id DESC LIMIT 1');$s->execute(['uid'=>$uid]);$r=$s->fetch();return !is_array($r)||$this->valid((string)($r['status']??''),$r['ends_at']??null);}
private function valid(string $status,mixed $ends):bool{return in_array($status,['trial','active'],true)&&($ends===null||strtotime((string)$ends)>time());}
private function isMasterAdmin(int $uid):bool{$s=$this->database->pdo()->prepare("SELECT 1 FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=:uid AND r.tenant_id IS NULL AND r.code='master_admin' LIMIT 1");$s->execute(['uid'=>$uid]);return $s->fetchColumn()!==false;}}
