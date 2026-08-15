<?php

declare(strict_types=1);

namespace Ispluka\Controllers;

use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Auth\Password;
use Ispluka\Core\Auth\RoleManager;
use Ispluka\Core\Database\Database;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
use Ispluka\Core\Security\Csrf;
use Throwable;

final class TenantController
{
    public function __construct(
        private readonly Database $database,
        private readonly AuthManager $auth,
        private readonly RoleManager $roles,
        private readonly Csrf $csrf,
    ) {
    }

    public function page(): Response
    {
        $this->requireMasterAdmin();
        $tenants = $this->database->pdo()->query(
            "SELECT t.id, t.name, t.code, t.legal_name, t.status, t.timezone, t.currency,
                    t.created_at,
                    (SELECT COUNT(*) FROM users u WHERE u.tenant_id = t.id AND u.deleted_at IS NULL) AS users_count,
                    (SELECT COUNT(*) FROM customers c WHERE c.tenant_id = t.id AND c.deleted_at IS NULL) AS customers_count
             FROM tenants t
             WHERE t.deleted_at IS NULL
             ORDER BY t.id DESC"
        )->fetchAll();

        $csrf = htmlspecialchars($this->csrf->token(), ENT_QUOTES, 'UTF-8');
        $created = isset($_GET['created']) && $_GET['created'] === '1';
        $rows = '';
        foreach ($tenants as $tenant) {
            $rows .= '<tr><td>'.(int)$tenant['id'].'</td><td><strong>'.htmlspecialchars((string)$tenant['name']).'</strong><br><span class="muted">'.htmlspecialchars((string)$tenant['code']).'</span></td><td>'.htmlspecialchars((string)$tenant['status']).'</td><td>'.(int)$tenant['users_count'].'</td><td>'.(int)$tenant['customers_count'].'</td><td>'.htmlspecialchars((string)$tenant['created_at']).'</td></tr>';
        }

        $notice = $created ? '<div class="card" style="border-color:#86efac;background:#f0fdf4;color:#166534;margin-bottom:1rem"><strong>Tenant and Admin created successfully.</strong></div>' : '';
        $html = '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#2563eb"><link rel="stylesheet" href="/assets/css/app.css"><title>Tenants - ISPLUKA</title></head><body><div class="app-shell"><header class="app-header"><div class="container header-inner"><button class="menu-toggle" type="button" data-menu-toggle aria-label="Open navigation" aria-expanded="false">☰</button><a class="brand" href="/">ISPLUKA</a><form method="post" action="/logout"><input type="hidden" name="_csrf" value="'.$csrf.'"><button class="btn-danger" type="submit">Sign out</button></form></div></header><aside class="sidebar" data-sidebar><nav class="nav"><a href="/">Dashboard</a><a class="active" href="/admin/tenants">Tenants / Admins</a><a href="/api/customers">Customers</a><a href="/api/customer-services">Services</a><a href="/networking/customer">Customer Networking</a><a href="/networking/mikrotik/enforcement-audit">MikroTik Audit</a></nav></aside><main class="main main-with-sidebar"><div class="container">'.$notice.'<div class="page-title"><div><h1>Tenants / Admins</h1><p class="muted">Create an ISP tenant and its first Admin account.</p></div><a class="btn btn-secondary" href="/">Dashboard</a></div><section class="card stack"><form method="post" action="/admin/tenants" class="stack"><input type="hidden" name="_csrf" value="'.$csrf.'"><h2>New Tenant</h2><div class="form-grid"><div><label>ISP Name *</label><input name="name" required maxlength="160"></div><div><label>Tenant Code *</label><input name="code" required maxlength="50" pattern="[A-Za-z0-9_-]+" placeholder="myisp"></div><div><label>Legal Name</label><input name="legal_name" maxlength="200"></div><div><label>Timezone</label><input name="timezone" value="Asia/Dhaka" maxlength="64"></div></div><h3>First Admin Account</h3><div class="form-grid"><div><label>Admin Name *</label><input name="admin_name" required maxlength="160"></div><div><label>Admin Email</label><input type="email" name="admin_email" maxlength="190"></div><div><label>Admin Phone</label><input name="admin_phone" maxlength="30"></div><div><label>Password *</label><input type="password" name="admin_password" required minlength="8" autocomplete="new-password"></div></div><button type="submit">Create Tenant + Admin</button></form></section><section class="card"><h2>Existing Tenants</h2><div class="table-wrap"><table><thead><tr><th>ID</th><th>Tenant</th><th>Status</th><th>Users</th><th>Customers</th><th>Created</th></tr></thead><tbody>'.$rows.'</tbody></table></div></section></div></main></div><script src="/assets/js/app.js" defer></script></body></html>';
        return Response::text($html);
    }

    public function store(Request $request): Response
    {
        $this->requireMasterAdmin();
        $name = trim((string) $request->input('name', ''));
        $code = strtolower(trim((string) $request->input('code', '')));
        $legalName = trim((string) $request->input('legal_name', ''));
        $timezone = trim((string) $request->input('timezone', 'Asia/Dhaka')) ?: 'Asia/Dhaka';
        $adminName = trim((string) $request->input('admin_name', ''));
        $adminEmail = trim((string) $request->input('admin_email', ''));
        $adminPhone = trim((string) $request->input('admin_phone', ''));
        $password = (string) $request->input('admin_password', '');

        if ($name === '' || $adminName === '' || $password === '' || !preg_match('/^[a-z0-9_-]{2,50}$/', $code)) {
            return Response::json(['error' => ['message' => 'Tenant name, valid tenant code, admin name and password are required.']], 422);
        }
        if (strlen($password) < 8) {
            return Response::json(['error' => ['message' => 'Admin password must be at least 8 characters.']], 422);
        }

        $pdo = $this->database->pdo();
        try {
            $pdo->beginTransaction();
            $tenant = $pdo->prepare('INSERT INTO tenants (name, code, legal_name, timezone, currency) VALUES (:name, :code, :legal_name, :timezone, \'BDT\') RETURNING id');
            $tenant->execute(['name' => $name, 'code' => $code, 'legal_name' => $legalName !== '' ? $legalName : null, 'timezone' => $timezone]);
            $tenantId = (int) $tenant->fetchColumn();

            $this->roles->provisionTenantRoles($tenantId);

            $user = $pdo->prepare('INSERT INTO users (tenant_id, name, email, phone, password_hash) VALUES (:tenant_id, :name, :email, :phone, :password_hash) RETURNING id');
            $user->execute(['tenant_id' => $tenantId, 'name' => $adminName, 'email' => $adminEmail !== '' ? $adminEmail : null, 'phone' => $adminPhone !== '' ? $adminPhone : null, 'password_hash' => Password::hash($password)]);
            $userId = (int) $user->fetchColumn();
            $this->roles->assign($userId, 'admin', $tenantId);
            $pdo->commit();

            return Response::redirect('/admin/tenants?created=1');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return Response::json(['error' => ['message' => $this->safeError($e)]], 422);
        }
    }

    private function requireMasterAdmin(): void
    {
        if (!$this->auth->userId()) throw new \RuntimeException('Authentication required.', 401);
        $statement = $this->database->pdo()->prepare(
            "SELECT 1 FROM user_roles ur INNER JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = :user_id AND r.tenant_id IS NULL AND r.code = 'master_admin' LIMIT 1"
        );
        $statement->execute(['user_id' => $this->auth->userId()]);
        if ($statement->fetchColumn() === false) throw new \RuntimeException('Forbidden.', 403);
    }

    private function safeError(Throwable $e): string
    {
        $message = $e->getMessage();
        if (str_contains(strtolower($message), 'duplicate') || str_contains(strtolower($message), 'unique')) return 'Tenant code or admin email already exists.';
        return 'Unable to create tenant. Please check the submitted data.';
    }
}
