<?php

declare(strict_types=1);
namespace Ispluka\Core\Api;
use Ispluka\Core\Http\Response;
final class ApiResponse {
 public static function success(mixed $data=null,int $status=200): Response { return Response::json(['success'=>true,'data'=>$data],$status); }
 public static function error(string $message,int $status=400,array $errors=[]): Response { return Response::json(['success'=>false,'message'=>$message,'errors'=>$errors],$status); }
}
