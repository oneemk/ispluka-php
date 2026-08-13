<?php

declare(strict_types=1);
namespace Ispluka\Core\Auth;
final class SessionManager {
 public function start():void { if(session_status()!==PHP_SESSION_ACTIVE){session_start(['cookie_secure'=>true,'cookie_httponly'=>true,'cookie_samesite'=>'Lax','use_strict_mode'=>true]);} }
 public function login(int $userId,int $tenantId,array $roles=[]):void { $this->start(); session_regenerate_id(true); $_SESSION['auth']=['user_id'=>$userId,'tenant_id'=>$tenantId,'roles'=>$roles,'login_at'=>time()]; }
 public function user():?array { $this->start(); return isset($_SESSION['auth'])&&is_array($_SESSION['auth'])?$_SESSION['auth']:null; }
 public function logout():void { $this->start(); $_SESSION=[]; if(ini_get('session.use_cookies')){ $p=session_get_cookie_params(); setcookie(session_name(),'',['expires'=>time()-42000,'path'=>$p['path'],'domain'=>$p['domain'],'secure'=>true,'httponly'=>true,'samesite'=>'Lax']); } session_destroy(); }
 public function csrfToken():string { $this->start(); return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32)); }
 public function verifyCsrf(string $token):bool { $this->start(); return isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'],$token); }
}
