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
    public function __construct(
        private readonly HotspotRepository $hotspot,
        private readonly HotspotCrudService $crud,
        private readonly HotspotActionService $actions,
        private readonly AuthManager $auth,
    ) {}

    public function profiles(): Response { return $this->json(fn () => $this->hotspot->profiles($this->tenantId())); }

    public function createProfile(Request $request): Response
    {
        try {
            $id = $this->crud->createProfile($this->tenantId(), [
                'name' => $request->input('name'), 'code' => $request->input('code'), 'validity' => $request->input('validity'),
                'rate_limit' => $request->input('rate_limit'), 'data_limit_bytes' => $request->input('data_limit_bytes'),
                'session_limit_seconds' => $request->input('session_limit_seconds'), 'shared_users' => $request->input('shared_users', 1),
            ]);
            return Response::json(['data' => ['id' => $id]], 201);
        } catch (Throwable $e) { return $this->error($e, 422); }
    }

    public function users(): Response { return $this->json(fn () => $this->hotspot->users($this->tenantId())); }

    public function sessions(Request $request): Response
    {
        $activeOnly = filter_var($request->query('active_only', 'true'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $this->json(fn () => $this->hotspot->sessions($this->tenantId(), $activeOnly !== false));
    }

    public function hosts(): Response { return $this->json(fn () => $this->hotspot->hosts($this->tenantId())); }
    public function bindings(): Response { return $this->json(fn () => $this->hotspot->bindings($this->tenantId())); }
    public function walledGarden(): Response { return $this->json(fn () => $this->hotspot->walledGarden($this->tenantId())); }
    public function addressLists(): Response { return $this->json(fn () => $this->hotspot->addressLists($this->tenantId())); }
    public function logs(): Response { return $this->json(fn () => $this->hotspot->logs($this->tenantId())); }

    public function disconnect(Request $request): Response
    {
        try {
            $sessionId = (int) $request->input('id', 0);
            if ($sessionId < 1) throw new InvalidArgumentException('Session ID is required.');
            $this->actions->disconnect($this->tenantId(), $sessionId, $this->auth->userId());
            return Response::json(['data' => ['disconnected' => true]]);
        } catch (Throwable $e) { return $this->error($e, 422); }
    }

    public function routerTime(Request $request): Response
    {
        try {
            $routerId = (int) $request->query('router_id', $request->input('router_id', 0));
            if ($routerId < 1) throw new InvalidArgumentException('Router ID is required.');
            return Response::json(['data' => $this->actions->syncRouterTime($this->tenantId(), $routerId, 10, $this->auth->userId())]);
        } catch (Throwable $e) { return $this->error($e, 422); }
    }

    public function activeUsers(Request $request): Response
    {
        try {
            $routerId = (int) $request->query('router_id', $request->input('router_id', 0));
            if ($routerId < 1) throw new InvalidArgumentException('Router ID is required.');
            return Response::json(['data' => $this->actions->activeUsers($this->tenantId(), $routerId, $this->auth->userId())]);
        } catch (Throwable $e) { return $this->error($e, 422); }
    }

    public function disableUser(Request $request): Response { return $this->toggleUser($request, false); }
    public function enableUser(Request $request): Response { return $this->toggleUser($request, true); }

    private function toggleUser(Request $request, bool $enable): Response
    {
        try {
            $routerId = (int) $request->input('router_id', 0);
            $username = trim((string) $request->input('username', ''));
            if ($routerId < 1 || $username === '') throw new InvalidArgumentException('Router ID and Hotspot username are required.');
            if ($enable) $this->actions->enableRouterUser($this->tenantId(), $routerId, $username, $this->auth->userId());
            else $this->actions->disableRouterUser($this->tenantId(), $routerId, $username, $this->auth->userId());
            return Response::json(['data' => ['username' => $username, 'enabled' => $enable]]);
        } catch (Throwable $e) { return $this->error($e, 422); }
    }

    private function tenantId(): int
    {
        $tenantId = $this->auth->tenantId();
        if ($tenantId === null || $tenantId < 1) throw new InvalidArgumentException('Tenant context is required.');
        return $tenantId;
    }

    private function json(callable $callback): Response
    {
        try { return Response::json(['data' => $callback()]); }
        catch (Throwable $e) { return $this->error($e); }
    }

    private function error(Throwable $e, int $status = 400): Response
    {
        $message = $e instanceof InvalidArgumentException || $e instanceof \RuntimeException ? $e->getMessage() : 'Unable to process Hotspot request.';
        return Response::json(['error' => ['message' => $message]], $status);
    }
}
