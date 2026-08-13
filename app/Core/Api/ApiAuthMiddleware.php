<?php

declare(strict_types=1);
namespace Ispluka\Core\Api;
use Ispluka\Core\Auth\SessionManager;
use Ispluka\Core\Http\Request;
use RuntimeException;
final class ApiAuthMiddleware {
 public function __construct(private readonly SessionManager $session) {}
 public function requireUser(Request $request):array{$user=$this->session->user();if(!$user)throw new RuntimeException('Authentication required.');return$user;}
 public function requireRole(Request $request,string ...$roles):array{$user=$this->requireUser($request);$actual=$user['roles']??[];if(!array_intersect($roles,$actual))throw new RuntimeException('Permission denied.');return$user;}
}
