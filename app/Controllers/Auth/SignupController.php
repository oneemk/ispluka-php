<?php

declare(strict_types=1);

namespace Ispluka\Controllers\Auth;

use Ispluka\Core\Auth\Password;
use Ispluka\Core\Auth\RoleManager;
use Ispluka\Core\Database\Database;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
use Ispluka\Core\Security\Csrf;
use Throwable;

final class SignupController
{
    public function __construct(private readonly Database $database, private readonly RoleManager $roles, private readonly Csrf $csrf) {}

    public function show(): Response
    {
        $token = htmlspecialchars($this->csrf->token(), ENT_QUOTES, 'UTF-8');
        return Response::text('<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#2563eb"><link rel="manifest" href="/manifest.json"><link rel="stylesheet" href="/assets/css/app.css"><title>Start 30-Day Trial · ISPLUKA</title></head><body class="login-page"><main class="login-card"><h1>ISPLUKA</h1><p class="muted">Create your Admin account and start a 30-day free trial.</p><form class="stack" method="post" action="/signup"><input type="hidden" name="_csrf" value="'.$token.'"><div><label>ISP / Company name</label><input name="name" maxlength="160" required></div><div><label>Tenant code</label><input name="code" maxlength="50" pattern="[A-Za-z0-9_-]+" placeholder="myisp" required></div><div><label>Your name</label><input name="admin_name" maxlength="160" required></div><div><label>Email</label><input type="email" name="admin_email" maxlength="190" required></div><div><label>Phone</label><input name="admin_phone" maxlength="30"></div><div><label>Password</label><input type="password" name="password" minlength="8" autocomplete="new-password" required></div><button type="submit">Start 30-Day Trial</button></form><p class="muted"><a href="/login">Already have an account? Sign in</a></p></main></body></html>');
    }

    public function store(Request $request): Response
    {
        if (!$this->csrf->validate((string)$request->input('_csrf',''))) return Response::text('Invalid CSRF token.',419);
        $name=trim((string)$request->input('name','')); $code=strtolower(trim((string)$request->input('code',''))); $adminName=trim((string)$request->input('admin_name','')); $email=trim((string)$request->input('admin_email','')); $phone=trim((string)$request->input('admin_phone','')); $password=(string)$request->input('password','');
        if ($name===''||$adminName===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<8||!preg_match('/^[a-z0-9_-]{2,50}$/',$code)) return Response::text('Please provide valid signup information.',422);
        $pdo=$this->database->pdo();
        try {
            $pdo->beginTransaction();
            $s=$pdo->prepare("INSERT INTO tenants (name,code,legal_name,timezone,currency,status) VALUES (:name,:code,NULL,'Asia/Dhaka','BDT','trial') RETURNING id"); $s->execute(['name'=>$name,'code'=>$code]); $tenantId=(int)$s->fetchColumn();
            $this->roles->provisionTenantRoles($tenantId);
            $u=$pdo->prepare('INSERT INTO users (tenant_id,name,email,phone,password_hash) VALUES (:tenant_id,:name,:email,:phone,:password_hash) RETURNING id'); $u->execute(['tenant_id'=>$tenantId,'name'=>$adminName,'email'=>$email,'phone'=>$phone!==''?$phone:null,'password_hash'=>Password::hash($password)]); $userId=(int)$u->fetchColumn(); $this->roles->assign($userId,'admin',$tenantId);
            $pdo->prepare("INSERT INTO tenant_subscriptions (tenant_id,plan_code,amount,starts_at,ends_at,status,metadata) VALUES (:tenant,'trial',0,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP+INTERVAL '30 days','trial','{}'::jsonb)")->execute(['tenant'=>$tenantId]);
            $pdo->commit(); return Response::redirect('/login?signup=success');
        } catch (Throwable $e) { if($pdo->inTransaction())$pdo->rollBack(); return Response::text(str_contains(strtolower($e->getMessage()),'duplicate')?'Tenant code or email already exists.':'Unable to create your trial account.',422); }
    }
}
