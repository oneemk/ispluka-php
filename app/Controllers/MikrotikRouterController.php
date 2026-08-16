<?php

declare(strict_types=1);

namespace Ispluka\Controllers;

use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
use Ispluka\Core\Security\Csrf;
use Ispluka\Services\RouterService;
use InvalidArgumentException;
use Throwable;

final class MikrotikRouterController
{
    public function __construct(private readonly RouterService $routers,private readonly AuthManager $auth,private readonly Csrf $csrf){}

    public function page():Response
    {
        $token=htmlspecialchars($this->csrf->token(),ENT_QUOTES,'UTF-8');
        return Response::text('<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#2563eb"><link rel="stylesheet" href="/assets/css/app.css"><title>MikroTik Routers</title></head><body><main class="main"><div class="container"><div class="page-title"><div><h1>MikroTik Routers</h1><p class="muted">Choose RouterOS API or SSH for each real router. Status is checked against the selected connection method.</p></div><a class="btn btn-secondary" href="/">Back</a></div><section class="card stack"><h2 id="form-title">Add Router</h2><form id="router-form" class="form-grid"><input type="hidden" name="_csrf" value="'.$token.'"><input type="hidden" name="id" value=""><div><label>Name</label><input name="name" required maxlength="120"></div><div><label>Code</label><input name="code" required maxlength="80"></div><div><label>Host / IP</label><input name="host" required maxlength="255"></div><div><label>Login Method</label><select name="connection_method" id="connection-method"><option value="api">RouterOS API</option><option value="ssh">SSH</option></select></div><div data-api-field><label>API Port</label><input name="api_port" value="8728" type="number" min="1" max="65535"></div><div data-ssh-field hidden><label>SSH Port</label><input name="ssh_port" value="22" type="number" min="1" max="65535"></div><div data-api-field><label>SSL API Port (optional)</label><input name="api_ssl_port" type="number" min="1" max="65535"></div><div><label>Username</label><input name="username" required maxlength="120" autocomplete="off"></div><div><label>Password</label><input name="password" type="password" autocomplete="new-password" placeholder="Required for new router; optional when editing"></div><div data-api-field><label class="checkbox"><input name="verify_ssl" type="checkbox" checked> Verify SSL certificate</label></div><div class="actions"><button type="submit" id="save-router">Add Router</button><button type="button" class="btn-secondary" id="cancel-edit" hidden>Cancel Edit</button></div><p class="muted" id="form-message"></p></form></section><section class="card"><div class="page-title"><div><h2>Routers</h2><p class="muted" id="live-status">Loading router inventory…</p></div><button type="button" class="btn-secondary" id="refresh">Refresh</button></div><div class="table-wrap"><table><thead><tr><th>Name</th><th>Code</th><th>Host</th><th>Login</th><th>Status</th><th>Last Seen</th><th>Actions</th></tr></thead><tbody id="routers"><tr><td colspan="7">Loading…</td></tr></tbody></table></div></section></div></main><script src="/assets/js/mikrotik-routers.js" defer></script></body></html>');
    }

    public function index(Request $request):Response{try{return Response::json(['data'=>$this->routers->list($this->tenantId())]);}catch(Throwable $e){return $this->error($e);}}
    public function store(Request $request):Response{try{$id=$this->routers->create($this->tenantId(),$this->input($request,true));return Response::json(['data'=>['id'=>$id]],201);}catch(Throwable $e){return $this->error($e,422);}}
    public function update(Request $request):Response{try{$id=(int)$request->input('id',0);if($id<=0)throw new InvalidArgumentException('Router ID is required.');$this->routers->update($this->tenantId(),$id,$this->input($request,false));return Response::json(['data'=>['id'=>$id,'updated'=>true]]);}catch(Throwable $e){return $this->error($e,422);}}
    public function test(Request $request):Response{try{$id=(int)$request->input('id',$request->query('id',0));$result=$this->routers->testConnection($this->tenantId(),$id);return Response::json(['data'=>$result],$result['ok']?200:502);}catch(Throwable $e){return $this->error($e,422);}}
    public function status(Request $request):Response{try{$id=(int)$request->query('id',$request->input('id',0));$result=$this->routers->status($this->tenantId(),$id);return Response::json(['data'=>$result],$result['ok']?200:502);}catch(Throwable $e){return $this->error($e,422);}}
    public function delete(Request $request):Response{try{$this->routers->delete($this->tenantId(),(int)$request->input('id',0));return Response::json(['data'=>['deleted'=>true]]);}catch(Throwable $e){return $this->error($e,422);}}
    private function input(Request $request,bool $creating):array{return ['name'=>$request->input('name'),'code'=>$request->input('code'),'host'=>$request->input('host'),'connection_method'=>$request->input('connection_method','api'),'api_port'=>$request->input('api_port',8728),'api_ssl_port'=>$request->input('api_ssl_port'),'ssh_port'=>$request->input('ssh_port',22),'username'=>$request->input('username'),'password'=>$request->input('password'),'verify_ssl'=>$request->input('verify_ssl')!==null&&$request->input('verify_ssl')!=='0'];}
    private function tenantId():int{$id=$this->auth->tenantId();if($id!==null)return $id;if($this->auth->roleCode()==='master_admin')return 0;throw new InvalidArgumentException('Tenant context is required.');}
    private function error(Throwable $e,int $status=400):Response{return Response::json(['error'=>['message'=>$e instanceof InvalidArgumentException||$e instanceof \RuntimeException?$e->getMessage():'Unable to process router request.']],$status);}
}
