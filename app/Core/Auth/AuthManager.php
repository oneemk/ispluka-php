<?php

declare(strict_types=1);

namespace Ispluka\Core\Auth;

use Ispluka\Core\Database\Database;

final class AuthManager
{
    private const SESSION_KEY='auth.user_id'; private const SESSION_TENANT_KEY='auth.tenant_id'; private const SESSION_ROLE_KEY='auth.role_code';
    public function __construct(private readonly Database $database,private readonly Session $session){}
    public function attempt(string $login,string $password,?string $roleCode=null):bool{
        $login=trim($login);if($login===''||$password==='')return false;
        $sql="SELECT u.id,u.tenant_id,u.password_hash,u.status,u.failed_login_attempts,u.locked_until,
            COALESCE((SELECT r.code FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=u.id ORDER BY CASE r.code WHEN 'master_admin' THEN 0 WHEN 'admin' THEN 1 WHEN 'reseller' THEN 2 WHEN 'employee' THEN 3 WHEN 'customer' THEN 4 ELSE 9 END,r.id LIMIT 1),'') role_code
            FROM users u WHERE (u.email=:login OR u.username=:login) AND u.deleted_at IS NULL LIMIT 1";
        $st=$this->database->pdo()->prepare($sql);$st->execute(['login'=>$login]);$user=$st->fetch();
        if(!is_array($user)||($user['status']??'')!=='active'||$this->isLocked($user['locked_until']??null))return false;
        if(!Password::verify($password,(string)$user['password_hash'])){$this->recordFailedAttempt((int)$user['id']);return false;}
        $this->clearFailedAttempts((int)$user['id']);$this->session->regenerate();$this->session->put(self::SESSION_KEY,(int)$user['id']);$this->session->put(self::SESSION_TENANT_KEY,$user['tenant_id']!==null?(int)$user['tenant_id']:null);$this->session->put(self::SESSION_ROLE_KEY,(string)($user['role_code']??''));return true;
    }
    public function check():bool{return $this->userId()!==null;} public function userId():?int{$v=$this->session->get(self::SESSION_KEY);return is_int($v)||(is_string($v)&&ctype_digit($v))?(int)$v:null;} public function tenantId():?int{$v=$this->session->get(self::SESSION_TENANT_KEY);return is_int($v)||(is_string($v)&&ctype_digit($v))?(int)$v:null;} public function roleCode():?string{$v=$this->session->get(self::SESSION_ROLE_KEY);return is_string($v)&&$v!==''?$v:null;} public function logout():void{$this->session->invalidate();}
    private function isLocked(mixed $v):bool{return is_string($v)&&$v!==''&&strtotime($v)!==false&&strtotime($v)>time();}
    private function recordFailedAttempt(int $id):void{$this->database->pdo()->prepare("UPDATE users SET failed_login_attempts=failed_login_attempts+1,locked_until=CASE WHEN failed_login_attempts+1>=:threshold THEN CURRENT_TIMESTAMP+INTERVAL '15 minutes' ELSE locked_until END WHERE id=:id")->execute(['threshold'=>5,'id'=>$id]);}
    private function clearFailedAttempts(int $id):void{$this->database->pdo()->prepare('UPDATE users SET failed_login_attempts=0,locked_until=NULL,last_login_at=CURRENT_TIMESTAMP WHERE id=:id')->execute(['id'=>$id]);}
}
