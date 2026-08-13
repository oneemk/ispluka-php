<?php

declare(strict_types=1);

namespace Ispluka\Core\Exceptions;

use Ispluka\Core\Http\Response;
use Throwable;

final class Handler
{
    public function render(Throwable $exception): Response
    {
        $status = $exception->getCode();
        $status = ($status >= 400 && $status <= 599) ? $status : 500;

        $debug = filter_var((string) ($_ENV['APP_DEBUG'] ?? 'false'), FILTER_VALIDATE_BOOL);

        if ($debug) {
            return Response::text(
                '<h1>Application Error</h1><pre>' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>',
                $status,
            );
        }

        return Response::text('<h1>Application Error</h1><p>An unexpected error occurred.</p>', $status);
    }
}
