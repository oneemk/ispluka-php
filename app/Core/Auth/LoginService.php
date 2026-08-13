<?php

declare(strict_types=1);
namespace Ispluka\Core\Auth;
use Ispluka\Core\Database\Database;
use RuntimeException;
final class LoginService {
 public function __construct(private readonly Database $db, private readonly PasswordService $passwords, private readonly SessionManager $session, private readonly LoginThrottle $throttle) {}
 public function login(string $email,string $password,string $ip):void {
  $email=strtolower(trim($email)); $key=hash('sha256',$email.'|'.$ip);
  if(!$this->throttle->allowed($key)) throw new RuntimeException('Too many login attempts. Please try again later.');
  $s=$this->db->pdo()->prepare('SELECT id,tenant_id,password_hash,status FROM users WHERE LOWER(email)=:email LIMIT 1'); $s->execute([':email'=>$email]); $u=$s->fetch();
  if(!$u || $u['status']!=='active' || !$this->passwords->verify($password,(string)$u['password_hash'])) throw new RuntimeException('Invalid credentials.');
  if($this->passwords->needsRehash((string)$u['password_hash'])) { $h=$this->passwords->hash($password); $x=$this->db->pdo()->prepare('UPDATE users SET password_hash=:h,updated_at=CURRENT_TIMESTAMP WHERE id=:id'); $x->execute([':h'=>$h,':id'=>$u['id']]); }
  $r=$this->db->pdo()->prepare('SELECT r.code FROM roles r JOIN user_roles ur ON ur.role_id=r.id WHERE ur.user_id=:id'); $r->execute([':id'=>$u['id']]);
  $this->session->login((int)$u['id'],(int)$u['tenant_id'],$r->fetchAll(\PDO::FETCH_COLUMN));
 }
}
