<?php

declare(strict_types=1);

namespace Ispluka\Core;

use Ispluka\Core\Exceptions\Handler;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
use Ispluka\Core\Routing\Router;

final class Application
{
    public function __construct(
        private readonly Router $router,
        private readonly Handler $exceptionHandler,
    ) {
    }

    public function run(): void
    {
        try {
            $request = Request::capture();
            $response = $this->router->dispatch($request);
            $response->send();
        } catch (\Throwable $exception) {
            $this->exceptionHandler->render($exception)->send();
        }
    }
}
