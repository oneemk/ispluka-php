<?php

declare(strict_types=1);
namespace Ispluka\Controllers\Api;
use Ispluka\Core\Api\ApiResponse;
use Ispluka\Core\Hotspot\HotspotRepository;
use Ispluka\Core\Http\Request;
use PDO;
final class HotspotController {
 public function __construct(private readonly PDO $pdo) {}
 private function tenant(Request $request):int{return (int)($request->attribute('tenant_id')??0);}
 private function repo():HotspotRepository{return new HotspotRepository($this->pdo);}
 public function profiles(Request $r):\Ispluka\Core\Http\Response{return ApiResponse::success(['data'=>$this->repo()->profiles($this->tenant($r))]);}
 public function users(Request $r):\Ispluka\Core\Http\Response{return ApiResponse::success(['data'=>$this->repo()->users($this->tenant($r))]);}
 public function sessions(Request $r):\Ispluka\Core\Http\Response{return ApiResponse::success(['data'=>$this->repo()->sessions($this->tenant($r))]);}
 public function bindings(Request $r):\Ispluka\Core\Http\Response{return ApiResponse::success(['data'=>$this->repo()->bindings($this->tenant($r))]);}
 public function hosts(Request $r):\Ispluka\Core\Http\Response{return ApiResponse::success(['data'=>$this->repo()->hosts($this->tenant($r))]);}
 public function walledGarden(Request $r):\Ispluka\Core\Http\Response{return ApiResponse::success(['data'=>$this->repo()->walledGarden($this->tenant($r))]);}
 public function addressLists(Request $r):\Ispluka\Core\Http\Response{return ApiResponse::success(['data'=>$this->repo()->addressLists($this->tenant($r))]);}
 public function logs(Request $r):\Ispluka\Core\Http\Response{return ApiResponse::success(['data'=>$this->repo()->logs($this->tenant($r))]);}
}
