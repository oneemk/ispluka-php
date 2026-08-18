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

        // Always keep the real exception on the server log. The production
        // response must remain generic so database credentials, SQL details,
        // filesystem paths and other internals are never exposed to users.
        error_log(sprintf(
            '[ISPLUKA] %s %s | %s: %s\n%s',
            $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            $_SERVER['REQUEST_URI'] ?? '-',
            $exception::class,
            $exception->getMessage(),
            $exception->getTraceAsString(),
        ));

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
