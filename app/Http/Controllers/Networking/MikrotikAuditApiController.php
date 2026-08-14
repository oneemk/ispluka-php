<?php

declare(strict_types=1);

namespace Ispluka\Http\Controllers\Networking;

use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
use PDO;

final class MikrotikAuditApiController
{
    public function __construct(private readonly PDO $pdo, private readonly AuthManager $auth) {}

    public function index(Request $request): Response
    {
        $tenantId = $this->auth->tenantId();
        if ($tenantId < 1) {
            return Response::json(['error' => ['message' => 'Authentication required.']], 401);
        }

        $status = (string) ($request->input('status') ?? '');
        $severity = (string) ($request->input('severity') ?? '');
        $router = (int) ($request->input('router_id') ?? 0);
        $where = ['tenant_id=:tenant'];
        $params = [':tenant' => $tenantId];

        if (in_array($status, ['open', 'resolved'], true)) {
            $where[] = 'status=:status';
            $params[':status'] = $status;
        }
        if (in_array($severity, ['critical', 'high', 'warning', 'info'], true)) {
            $where[] = 'severity=:severity';
            $params[':severity'] = $severity;
        }
        if ($router > 0) {
            $where[] = 'router_id=:router';
            $params[':router'] = $router;
        }

        $sql = 'SELECT id,router_id,username,finding_type,severity,message,details,status,first_seen_at,last_seen_at,resolved_at FROM pppoe_reconciliation_findings WHERE ' . implode(' AND ', $where) . ' ORDER BY CASE severity WHEN \'critical\' THEN 1 WHEN \'high\' THEN 2 WHEN \'warning\' THEN 3 ELSE 4 END,last_seen_at DESC LIMIT 500';
        $q = $this->pdo->prepare($sql);
        $q->execute($params);

        return Response::json(['data' => $q->fetchAll(PDO::FETCH_ASSOC)]);
    }
}
