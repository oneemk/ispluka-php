<?php

declare(strict_types=1);

namespace Ispluka\Controllers;

use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
use Ispluka\Core\Network\MikrotikAutomationService;
use Throwable;

final class MikrotikManualActionController
{
    public function __construct(private readonly AuthManager $auth, private readonly MikrotikAutomationService $automation) {}

    public function execute(Request $request): Response
    {
        $tenantId = (int)($this->auth->tenantId() ?? 0);
        $serviceId = (int)$request->input('service_id', 0);
        $action = strtolower(trim((string)$request->input('action', '')));
        if ($tenantId < 1 || $serviceId < 1 || !in_array($action, ['enable','disable','suspend'], true)) {
            return Response::json(['error'=>['message'=>'Service and a valid action are required.']], 422);
        }
        try {
            $result = $this->automation->execute(
                $tenantId,
                $serviceId,
                $action,
                'manual',
                (int)($this->auth->userId() ?? 0)
            );
            $code = ($result['status'] ?? '') === 'success' ? 200 : 409;
            return Response::json(['data'=>$result], $code);
        } catch (Throwable $e) {
            return Response::json(['error'=>['message'=>'MikroTik action failed.'],'data'=>['status'=>'failed']], 502);
        }
    }
}
