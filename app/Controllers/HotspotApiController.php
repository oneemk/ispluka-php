<?php

declare(strict_types=1);

namespace Ispluka\Controllers;

use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Hotspot\HotspotActionService;
use Ispluka\Core\Hotspot\HotspotCrudService;
use Ispluka\Core\Hotspot\HotspotRepository;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
use InvalidArgumentException;
use Throwable;

final class HotspotApiController
{
    public function __construct(private readonly HotspotRepository $hotspot, private readonly HotspotCrudService $crud, private readonly HotspotActionService $actions, private readonly AuthManager $auth) {}
    public function profiles():Response{return$this->json(fn()=> $this->hotspot->profiles($this->tenantId()));}
    public function createProfile(Request $r):Response{try{$id=$this->crud->createProfile($this->tenantId(),$r->all());return Response::json(['data'=>['id'=>$id]],201);}catch(Throwable$e){return$this->error($e,422);}}
    public function updateProfile(Request $r):Response{try{$id=(int)$r->input('id',0);if($id<1)throw new InvalidArgumentException('Profile ID is required.');$this->crud->updateProfile($this->tenantId(),$id,$r->all());return Response::json(['data'=>['updated'=>true]]);}catch(Throwable$e){return$this->error($e,422);}}
    public function deleteProfile(Request $r):Response{try{$id=(int)$r->input('id',0);if($id<1)throw new InvalidArgumentException('Profile ID is required.');$this->crud->deleteProfile($this->tenantId(),$id);return Response::json(['data'=>['deleted'=>true]]);}catch(Throwable$e){return$this->error($e,422);}}
    public function users():Response{return$this->json(fn()=> $this->hotspot->users($this->tenantId()));}
    public function createUser(Request $r):Response{try{$id=$this->crud->createUser($this->tenantId(),$r->all());$routerId=(int)$r->input('router_id',0);$password=(string)$r->input('password','');if($routerId>0&&$password!=='')$this->actions->createRouterUser($this->tenantId(),$routerId,['name'=>$r->input('username'),'password'=>$password,'profile'=>$r->input('router_profile'),'mac-address'=>$r->input('mac_address')],$this->auth->userId());return Response::json(['data'=>['id'=>$id]],201);}catch(Throwable$e){return$this->error($e,422);}}
    public function updateUser(Request $r):Response{try{$id=(int)$r->input('id',0);if($id<1)throw new InvalidArgumentException('User ID is required.');$this->crud->updateUser($this->tenantId(),$id,$r->all());return Response::json(['data'=>['updated'=>true]]);}catch(Throwable$e){return$this->error($e,422);}}
    public function activateUser(Request $r):Response{try{$id=(int)$r->input('id',0);if($id<1)throw new InvalidArgumentException('User ID is required.');return Response::json(['data'=>$this->crud->activateUser($this->tenantId(),$id)]);}catch(Throwable$e){return$this->error($e,422);}}
    public function disableUser(Request $r):Response{try{$id=(int)$r->input('id',0);$this->crud->setUserStatus($this->tenantId(),$id,'disabled');return Response::json(['data'=>['disabled'=>true]]);}catch(Throwable$e){return$this->error($e,422);}}
    public function enableUser(Request $r):Response{try{$id=(int)$r->input('id',0);$this->crud->setUserStatus($this->tenantId(),$id,'unused');return Response::json(['data'=>['enabled'=>true]]);}catch(Throwable$e){return$this->error($e,422);}}
    public function sessions(Request $r):Response{$activeOnly=filter_var($r->query('active_only','true'),FILTER_VALIDATE_BOOLEAN,FILTER_NULL_ON_FAILURE);return$this->json(fn()=> $this->hotspot->sessions($this->tenantId(),$activeOnly!==false));}
    public function hosts():Response{return$this->json(fn()=> $this->hotspot->hosts($this->tenantId()));}
    public function bindings():Response{return$this->json(fn()=> $this->hotspot->bindings($this->tenantId()));}
    public function walledGarden():Response{return$this->json(fn()=> $this->hotspot->walledGarden($this->tenantId()));}
    public function addressLists():Response{return$this->json(fn()=> $this->hotspot->addressLists($this->tenantId()));}
    public function logs():Response{return$this->json(fn()=> $this->hotspot->logs($this->tenantId()));}
    public function disconnect(Request $r):Response{try{$id=(int)$r->input('id',0);if($id<1)throw new InvalidArgumentException('Session ID is required.');$this->actions->disconnect($this->tenantId(),$id,$this->auth->userId());return Response::json(['data'=>['disconnected'=>true]]);}catch(Throwable$e){return$this->error($e,422);}}
    public function routerTime(Request $r):Response{try{$routerId=(int)$r->query('router_id',$r->input('router_id',0));if($routerId<1)throw new InvalidArgumentException('Router ID is required.');return Response::json(['data'=>$this->actions->syncRouterTime($this->tenantId(),$routerId,10,$this->auth->userId())]);}catch(Throwable$e){return$this->error($e,422);}}
    public function activeUsers(Request $r):Response{try{$routerId=(int)$r->query('router_id',$r->input('router_id',0));if($routerId<1)throw new InvalidArgumentException('Router ID is required.');return Response::json(['data'=>$this->actions->activeUsers($this->tenantId(),$routerId,$this->auth->userId())]);}catch(Throwable$e){return$this->error($e,422);}}
    public function syncSessions(Request $r):Response{try{$routerId=(int)$r->input('router_id',0);if($routerId<1)throw new InvalidArgumentException('Router ID is required.');return Response::json(['data'=>$this->actions->syncSessions($this->tenantId(),$routerId,$this->auth->userId())]);}catch(Throwable$e){return$this->error($e,422);}}
    public function disableRouterUser(Request $r):Response{return$this->toggleRouterUser($r,false);}
    public function enableRouterUser(Request $r):Response{return$this->toggleRouterUser($r,true);}
    private function toggleRouterUser(Request $r,bool $enable):Response{try{$routerId=(int)$r->input('router_id',0);$username=trim((string)$r->input('username',''));if($routerId<1||$username==='')throw new InvalidArgumentException('Router ID and Hotspot username are required.');if($enable)$this->actions->enableRouterUser($this->tenantId(),$routerId,$username,$this->auth->userId());else$this->actions->disableRouterUser($this->tenantId(),$routerId,$username,$this->auth->userId());return Response::json(['data'=>['username'=>$username,'enabled'=>$enable]]);}catch(Throwable$e){return$this->error($e,422);}}
    private function tenantId():int{$id=$this->auth->tenantId();if($id===null||$id<1)throw new InvalidArgumentException('Tenant context is required.');return$id;}
    private function json(callable $callback):Response{try{return Response::json(['data'=>$callback()]);}catch(Throwable$e){return$this->error($e);}}
    private function error(Throwable$e,int$status=400):Response{$message=$e instanceof InvalidArgumentException||$e instanceof \RuntimeException?$e->getMessage():'Unable to process Hotspot request.';return Response::json(['error'=>['message'=>$message]],$status);}
}
