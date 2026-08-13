<?php

declare(strict_types=1);

namespace Ispluka\Controllers;

use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
use Ispluka\Services\CustomerService;
use InvalidArgumentException;
use Throwable;

final class CustomerController
{
    public function __construct(
        private readonly CustomerService $customers,
        private readonly AuthManager $auth,
    ) {
    }

    public function index(Request $request): Response
    {
        try {
            $tenantId = $this->tenantId();
            $page = max(1, (int) $request->query('page', 1));
            $perPage = max(1, min(100, (int) $request->query('per_page', 25)));
            return Response::json(['data' => $this->customers->list($tenantId, $page, $perPage, $request->query('search'))]);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    public function show(Request $request): Response
    {
        try {
            $id = (int) $request->query('id', 0);
            return Response::json(['data' => $this->customers->get($this->tenantId(), $id)]);
        } catch (Throwable $e) {
            return $this->error($e, 404);
        }
    }

    public function store(Request $request): Response
    {
        try {
            $data = [
                'customer_code' => $request->input('customer_code'),
                'name' => $request->input('name'),
                'phone' => $request->input('phone'),
                'email' => $request->input('email'),
                'nid' => $request->input('nid'),
                'address' => $request->input('address'),
                'area' => $request->input('area'),
                'reseller_id' => $request->input('reseller_id'),
                'billing_day' => $request->input('billing_day', 1),
                'credit_limit' => $request->input('credit_limit', 0),
                'metadata' => [],
            ];
            $id = $this->customers->create($this->tenantId(), $data);
            return Response::json(['data' => ['id' => $id]], 201);
        } catch (Throwable $e) {
            return $this->error($e, $e instanceof InvalidArgumentException ? 422 : 400);
        }
    }

    public function update(Request $request): Response
    {
        try {
            $id = (int) $request->input('id', 0);
            $data = array_filter([
                'name' => $request->input('name'), 'phone' => $request->input('phone'), 'email' => $request->input('email'),
                'nid' => $request->input('nid'), 'address' => $request->input('address'), 'area' => $request->input('area'),
                'status' => $request->input('status'), 'billing_day' => $request->input('billing_day'),
                'credit_limit' => $request->input('credit_limit'), 'reseller_id' => $request->input('reseller_id'),
            ], static fn ($value) => $value !== null);
            $this->customers->update($this->tenantId(), $id, $data);
            return Response::json(['data' => ['updated' => true]]);
        } catch (Throwable $e) {
            return $this->error($e, 422);
        }
    }

    public function destroy(Request $request): Response
    {
        try {
            $this->customers->delete($this->tenantId(), (int) $request->input('id', 0));
            return Response::json(['data' => ['deleted' => true]]);
        } catch (Throwable $e) {
            return $this->error($e, 422);
        }
    }

    private function tenantId(): int
    {
        $tenantId = $this->auth->tenantId();
        if ($tenantId === null) throw new InvalidArgumentException('Tenant context is required.');
        return $tenantId;
    }

    private function error(Throwable $e, int $status = 400): Response
    {
        $message = $e instanceof InvalidArgumentException ? $e->getMessage() : 'Unable to process customer request.';
        return Response::json(['error' => ['message' => $message]], $status);
    }
}
