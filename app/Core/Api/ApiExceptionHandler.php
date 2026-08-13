<?php

declare(strict_types=1);
namespace Ispluka\Core\Api;
use Throwable;
use Ispluka\Core\Http\Response;
final class ApiExceptionHandler {
 public static function render(Throwable $e): Response { return ApiResponse::error('Request could not be completed.',500); }
}
