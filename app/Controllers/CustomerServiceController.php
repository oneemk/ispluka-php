<?php

declare(strict_types=1);

namespace Ispluka\Controllers;

use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
use Ispluka\Services\CustomerAccessService;
use InvalidArgumentException;
use Throwable;

final class CustomerServiceController
{
    public function __construct(
        private readonly CustomerAccessService $services,
        private readonly AuthManager $auth,
    ) {
    }

    public function index(Request $request): Response
    {
        try {
            $customerId = (int) $request->query('customer_id', 0);
            return Response::json(['data' => $this->services->list($this->tenantId(), $customerId)]);
        } catch (Throwable $e) {
            return $this->error($e, 422);
        }
    }

    public function store(Request $request): Response
    {
        try {
            $secret = $request->input('secret');
            $encryptedSecret = is_string($secret) && $secret !== '' ? base64_encode($secret) : null;
            $id = $this->services->create($this->tenantId(), (int) $request->input('customer_id', 0), [
                'package_id' => $request->input('package_id'),
                'router_id' => $request->input('router_id'),
                'service_type' => $request->input('service_type'),
                'username' => $request->input('username'),
                'encrypted_secret' => $encryptedSecret,
                'mac_address' => $request->input('mac_address'),
                'ip_address' => $request->input('ip_address'),
                'start_date' => $request->input('start_date'),
                'next_billing_date' => $request->input('next_billing_date'),
                'auto_suspend' => $request->input('auto_suspend', true),
            ]);
            return Response::json(['data' => ['id' => $id]], 201);
        } catch (Throwable $e) {
            return $this->error($e, 422);
        }
    }

    public function status(Request $request): Response
    {
        try {
            $this->services->changeStatus($this->tenantId(), (int) $request->input('service_id', 0), (string) $request->input('status', ''));
            return Response::json(['data' => ['updated' => true]]);
        } catch (Throwable $e) {
            return $this->error($e, 422);
        }
    }

    private function tenantId(): int
    {
        $id = $this->auth->tenantId();
        if ($id === null) throw new InvalidArgumentException('Tenant context is required.');
        return $id;
    }

    private function error(Throwable $e, int $status): Response
    {
        $message = $e instanceof InvalidArgumentException ? $e->getMessage() : 'Unable to process service request.';
        return Response::json(['error' => ['message' => $message]], $status);
    }
}
