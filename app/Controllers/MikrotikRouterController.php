<?php

declare(strict_types=1);

namespace Ispluka\Controllers;

use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
use Ispluka\Services\RouterService;
use InvalidArgumentException;
use Throwable;

final class MikrotikRouterController
{
    public function __construct(
        private readonly RouterService $routers,
        private readonly AuthManager $auth,
    ) {}

    public function page(): Response
    {
        return Response::text(<<<'HTML'
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#2563eb"><link rel="stylesheet" href="/assets/css/app.css"><title>MikroTik Routers</title></head><body><main class="main"><div class="container"><div class="page-title"><div><h1>MikroTik Routers</h1><p class="muted">Manage routers for your ISP tenant.</p></div><a class="btn btn-secondary" href="/">Back</a></div><section class="card stack"><h2>Add Router</h2><form id="router-form" class="form-grid"><input type="hidden" name="_csrf" value=""><div><label>Name</label><input name="name" required></div><div><label>Code</label><input name="code" required></div><div><label>Host / IP</label><input name="host" required></div><div><label>API Port</label><input name="api_port" value="8728" type="number" min="1" max="65535" required></div><div><label>Username</label><input name="username" required></div><div><label>Password</label><input name="password" type="password" required></div><div><label>SSL API Port (optional)</label><input name="api_ssl_port" type="number" min="1" max="65535"></div><div><label><input name="verify_ssl" type="checkbox" checked> Verify SSL</label></div><div class="actions"><button type="submit">Add Router</button></div><p class="muted" id="form-message"></p></form></section><section class="card"><div class="page-title"><h2>Routers</h2><button type="button" class="btn-secondary" id="refresh">Refresh</button></div><div class="table-wrap"><table><thead><tr><th>Name</th><th>Code</th><th>Host</th><th>Status</th><th>Last Seen</th><th>Actions</th></tr></thead><tbody id="routers"><tr><td colspan="6">Loading…</td></tr></tbody></table></div></section></div></main><script src="/assets/js/mikrotik-routers.js" defer></script></body></html>
HTML);
    }

    public function index(Request $request): Response
    {
        try {
            return Response::json(['data' => $this->routers->list($this->tenantId())]);
        } catch (Throwable $e) { return $this->error($e); }
    }

    public function store(Request $request): Response
    {
        try {
            $id = $this->routers->create($this->tenantId(), [
                'name'=>$request->input('name'), 'code'=>$request->input('code'), 'host'=>$request->input('host'),
                'api_port'=>$request->input('api_port',8728), 'api_ssl_port'=>$request->input('api_ssl_port'),
                'username'=>$request->input('username'), 'password'=>$request->input('password'),
                'verify_ssl'=>$request->input('verify_ssl') !== null && $request->input('verify_ssl') !== '0',
            ]);
            return Response::json(['data'=>['id'=>$id]],201);
        } catch (Throwable $e) { return $this->error($e,422); }
    }

    public function test(Request $request): Response
    {
        try { return Response::json(['data'=>$this->routers->testConnection($this->tenantId(),(int)$request->input('id',0))]); }
        catch (Throwable $e) { return $this->error($e,422); }
    }

    public function delete(Request $request): Response
    {
        try { $this->routers->delete($this->tenantId(),(int)$request->input('id',0)); return Response::json(['data'=>['deleted'=>true]]); }
        catch (Throwable $e) { return $this->error($e,422); }
    }

    private function tenantId(): int
    {
        $id=$this->auth->tenantId();
        if ($id===null) throw new InvalidArgumentException('Tenant context is required.');
        return $id;
    }
    private function error(Throwable $e,int $status=400): Response
    { return Response::json(['error'=>['message'=>$e instanceof InvalidArgumentException?$e->getMessage():'Unable to process router request.']],$status); }
}
