<?php

declare(strict_types=1);

namespace Ispluka\Controllers;

use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
use Ispluka\Core\Network\PppoeActivityRepository;
use Ispluka\Core\Network\PppoeEnforcementAuditQuery;
use Ispluka\Core\Network\PppoeUsageReport;
use PDO;

final class MikrotikEnforcementAuditController
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly AuthManager $auth,
    ) {}

    private function tenant(): int
    {
        return (int) ($this->auth->tenantId() ?? 0);
    }

    public function audit(Request $r): Response
    {
        $tenant = $this->tenant();
        if ($tenant < 1) {
            return Response::json(['error' => ['message' => 'Tenant context required.']], 403);
        }

        $status = trim((string) ($r->input('status') ?? ''));
        $limit = (int) ($r->input('limit') ?? 50);
        $offset = (int) ($r->input('offset') ?? 0);

        return Response::json([
            'data' => (new PppoeEnforcementAuditQuery($this->pdo))->list(
                $tenant,
                $status !== '' ? $status : null,
                $limit,
                $offset,
            ),
        ]);
    }

    public function summary(Request $r): Response
    {
        $tenant = $this->tenant();
        if ($tenant < 1) {
            return Response::json(['error' => ['message' => 'Tenant context required.']], 403);
        }

        return Response::json([
            'data' => (new PppoeEnforcementAuditQuery($this->pdo))->summary($tenant),
        ]);
    }

    public function live(Request $r): Response
    {
        $tenant = $this->tenant();
        $router = (int) ($r->input('router_id') ?? 0);
        $username = trim((string) ($r->input('username') ?? ''));

        if ($tenant < 1 || $router < 1 || $username === '') {
            return Response::json(['error' => ['message' => 'Tenant, router_id and username are required.']], 422);
        }

        $row = (new PppoeActivityRepository($this->pdo))->find($tenant, $router, $username);
        if ($row === null) {
            return Response::json(['error' => ['message' => 'No activity snapshot found.']], 404);
        }

        $row['source'] = 'activity_snapshot';
        $row['note'] = 'Latest bounded MikroTik collection; this avoids per-user router polling.';

        return Response::json(['data' => $row]);
    }

    public function reconciliation(Request $r): Response
    {
        $tenant = $this->tenant();
        if ($tenant < 1) {
            return Response::json(['error' => ['message' => 'Tenant context required.']], 403);
        }

        $status = trim((string) ($r->input('status') ?? ''));
        $severity = trim((string) ($r->input('severity') ?? ''));
        $router = (int) ($r->input('router_id') ?? 0);

        $where = ['tenant_id = :tenant'];
        $params = [':tenant' => $tenant];

        if (in_array($status, ['open', 'resolved'], true)) {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }

        if (in_array($severity, ['critical', 'high', 'warning', 'info'], true)) {
            $where[] = 'severity = :severity';
            $params[':severity'] = $severity;
        }

        if ($router > 0) {
            $where[] = 'router_id = :router';
            $params[':router'] = $router;
        }

        $query = $this->pdo->prepare(
            'SELECT id, router_id, username, finding_type, severity, message, details, status, first_seen_at, last_seen_at, resolved_at
             FROM pppoe_reconciliation_findings
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY last_seen_at DESC LIMIT 500'
        );
        $query->execute($params);

        return Response::json(['data' => $query->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function usage(Request $r): Response
    {
        $tenant = $this->tenant();
        $router = (int) ($r->input('router_id') ?? 0);
        $username = trim((string) ($r->input('username') ?? ''));
        $from = trim((string) ($r->input('from') ?? ''));
        $to = trim((string) ($r->input('to') ?? ''));

        if ($tenant < 1 || $router < 1 || $username === '' || $from === '' || $to === '') {
            return Response::json(['error' => ['message' => 'Tenant, router_id, username, from and to are required.']], 422);
        }

        try {
            $fromTs = new \DateTimeImmutable($from);
            $toTs = new \DateTimeImmutable($to);
        } catch (\Throwable) {
            return Response::json(['error' => ['message' => 'Invalid date range.']], 422);
        }

        if ($toTs <= $fromTs) {
            return Response::json(['error' => ['message' => 'Invalid date range.']], 422);
        }

        $now = new \DateTimeImmutable('now');
        $min = $now->modify('-6 months');
        if ($fromTs < $min) {
            $fromTs = $min;
        }
        if ($toTs > $now) {
            $toTs = $now;
        }
        if ($toTs <= $fromTs) {
            return Response::json(['data' => []]);
        }

        $data = (new PppoeUsageReport($this->pdo))->monthly(
            $tenant,
            $router,
            $username,
            $fromTs->format('Y-m-d H:i:s'),
            $toTs->format('Y-m-d H:i:s'),
        );

        return Response::json([
            'data' => $data,
            'meta' => [
                'from' => $fromTs->format(DATE_ATOM),
                'to' => $toTs->format(DATE_ATOM),
                'retention_months' => 6,
            ],
        ]);
    }

    public function page(Request $r): Response
    {
        return Response::text(<<<'HTML'
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="/assets/css/app.css">
<title>MikroTik Audit</title>
</head>
<body>
<main class="wrap" data-page="mikrotik-audit">
<h1>MikroTik Audit</h1>
<p class="muted">ERP ↔ MikroTik reconciliation and PPPoE enforcement audit</p>
<section class="list" data-audit-list><div class="card muted">Loading…</div></section>
</main>
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const list = document.querySelector('[data-audit-list]');
    try {
        const response = await fetch('/api/mikrotik/pppoe/enforcement-audit?limit=100', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        });
        const json = await response.json();
        if (!response.ok) throw new Error(json.error?.message || 'Unable to load audit');
        list.textContent = JSON.stringify(json.data || [], null, 2);
    } catch (error) {
        list.textContent = error instanceof Error ? error.message : String(error);
    }
});
</script>
</body>
</html>
HTML);
    }
}
