<?php

declare(strict_types=1);
namespace Ispluka\Controllers\Api;
use Ispluka\Core\Api\ApiResponse;
use Ispluka\Core\Http\Request;
final class HealthController { public function __invoke(Request $request): \Ispluka\Core\Http\Response { return ApiResponse::success(['status'=>'ok','service'=>'ispluka-api','timestamp'=>gmdate('c')]); } }
