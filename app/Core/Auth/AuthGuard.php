<?php

declare(strict_types=1);
namespace Ispluka\Core\Auth;
use RuntimeException;
final class AuthGuard {
 public function __construct(private readonly SessionManager $session) {}
 public function requireUser():array { $user=$this->session->user(); if(!$user) throw new RuntimeException('Authentication required.'); return $user; }
 public function requireTenant(int $tenantId):array { $user=$this->requireUser(); if((int)$user['tenant_id']!==$tenantId) throw new RuntimeException('Tenant access denied.'); return $user; }
 public function requireRole(string $role):array { $user=$this->requireUser(); if(!in_array($role,$user['roles']??[],true)) throw new RuntimeException('Permission denied.'); return $user; }
}
