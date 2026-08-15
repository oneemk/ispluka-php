<?php

declare(strict_types=1);

namespace Ispluka\Middleware;

use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Database\Database;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;

final class SubscriptionGuard
{
    public function __construct(private readonly Database $database, private readonly AuthManager $auth) {}

    public function __invoke(Request $request, callable $next): Response
    {
        $userId = $this->auth->userId();
        if ($userId === null) return Response::redirect('/login');
        if ($this->isMasterAdmin($userId)) return $next($request);

        $role = $this->auth->roleCode();
        $status = $this->status($userId, $role);
        if ($status['active']) return $next($request);

        if ($request->path() === '/subscription') return $next($request);
        return Response::redirect('/subscription');
    }

    private function status(int $userId, ?string $role): array
    {
        $pdo = $this->database->pdo();
        if ($role === 'reseller') {
            $s = $pdo->prepare("SELECT status, ends_at FROM user_subscriptions WHERE user_id=:uid ORDER BY id DESC LIMIT 1");
            $s->execute(['uid'=>$userId]);
            $row = $s->fetch();
            if (is_array($row)) return ['active'=>$this->valid($row['status'] ?? '', $row['ends_at'] ?? null)];
        }
        $s = $pdo->prepare("SELECT status, ends_at FROM tenant_subscriptions WHERE tenant_id=(SELECT tenant_id FROM users WHERE id=:uid) ORDER BY id DESC LIMIT 1");
        $s->execute(['uid'=>$userId]);
        $row = $s->fetch();
        if (!is_array($row)) return ['active'=>true];
        return ['active'=>$this->valid($row['status'] ?? '', $row['ends_at'] ?? null)];
    }

    private function valid(string $status, mixed $endsAt): bool
    {
        if (!in_array($status, ['trial','active'], true)) return false;
        return $endsAt === null || strtotime((string)$endsAt) > time();
    }

    private function isMasterAdmin(int $userId): bool
    {
        $s = $this->database->pdo()->prepare("SELECT 1 FROM user_roles ur INNER JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=:uid AND r.tenant_id IS NULL AND r.code='master_admin' LIMIT 1");
        $s->execute(['uid'=>$userId]);
        return $s->fetchColumn() !== false;
    }
}
