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
        if ($tenant < 1) return Response::json(['error' => ['message' => 'Tenant context required.']], 403);
        $status = trim((string) ($r->input('status') ?? ''));
        $limit = (int) ($r->input('limit') ?? 50);
        $offset = (int) ($r->input('offset') ?? 0);
        return Response::json(['data' => (new PppoeEnforcementAuditQuery($this->pdo))->list($tenant, $status !== '' ? $status : null, $limit, $offset)]);
    }

    public function summary(Request $r): Response
    {
        $tenant = $this->tenant();
        if ($tenant < 1) return Response::json(['error' => ['message' => 'Tenant context required.']], 403);
        return Response::json(['data' => (new PppoeEnforcementAuditQuery($this->pdo))->summary($tenant)]);
    }

    public function live(Request $r): Response
    {
        $tenant = $this->tenant();
        $router = (int) ($r->input('router_id') ?? 0);
        $username = trim((string) ($r->input('username') ?? ''));
        if ($tenant < 1 || $router < 1 || $username === '') return Response::json(['error' => ['message' => 'Tenant, router_id and username are required.']], 422);
        $row = (new PppoeActivityRepository($this->pdo))->find($tenant, $router, $username);
        if ($row === null) return Response::json(['error' => ['message' => 'No activity snapshot found.']], 404);
        $row['source'] = 'activity_snapshot';
        $row['note'] = 'Latest bounded MikroTik collection; this avoids per-user router polling.';
        return Response::json(['data' => $row]);
    }

    public function reconciliation(Request $r): Response
    {
        $tenant = $this->tenant();
        if ($tenant < 1) return Response::json(['error' => ['message' => 'Tenant context required.']], 403);
        $status = trim((string) ($r->input('status') ?? ''));
        $severity = trim((string) ($r->input('severity') ?? ''));
        $router = (int) ($r->input('router_id') ?? 0);
        $where = ['tenant_id = :tenant'];
        $params = [':tenant' => $tenant];
        if (in_array($status, ['open', 'resolved'], true)) { $where[] = 'status = :status'; $params[':status'] = $status; }
        if (in_array($severity, ['critical', 'high', 'warning', 'info'], true)) { $where[] = 'severity = :severity'; $params[':severity'] = $severity; }
        if ($router > 0) { $where[] = 'router_id = :router'; $params[':router'] = $router; }
        $query = $this->pdo->prepare('SELECT id, router_id, username, finding_type, severity, message, details, status, first_seen_at, last_seen_at, resolved_at FROM pppoe_reconciliation_findings WHERE ' . implode(' AND ', $where) . ' ORDER BY CASE severity WHEN \'critical\' THEN 1 WHEN \'high\' THEN 2 WHEN \'warning\' THEN 3 ELSE 4 END,last_seen_at DESC LIMIT 500');
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
        if ($tenant < 1 || $router < 1 || $username === '' || $from === '' || $to === '') return Response::json(['error' => ['message' => 'Tenant, router_id, username, from and to are required.']], 422);
        try { $fromTs = new \DateTimeImmutable($from); $toTs = new \DateTimeImmutable($to); } catch (\Throwable) { return Response::json(['error' => ['message' => 'Invalid date range.']], 422); }
        if ($toTs <= $fromTs) return Response::json(['error' => ['message' => 'Invalid date range.']], 422);
        $now = new \DateTimeImmutable('now'); $min = $now->modify('-6 months');
        if ($fromTs < $min) $fromTs = $min;
        if ($toTs > $now) $toTs = $now;
        if ($toTs <= $fromTs) return Response::json(['data' => []]);
        $data = (new PppoeUsageReport($this->pdo))->monthly($tenant, $router, $username, $fromTs->format('Y-m-d H:i:s'), $toTs->format('Y-m-d H:i:s'));
        return Response::json(['data' => $data, 'meta' => ['from' => $fromTs->format(DATE_ATOM), 'to' => $toTs->format(DATE_ATOM), 'retention_months' => 6]]);
    }

    public function page(Request $r): Response
    {
        return Response::text(<<<'HTML'
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="/assets/css/app.css"><title>MikroTik Audit</title>
<style>
.audit-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:18px 0}.audit-card{padding:16px;border:1px solid #ddd;border-radius:10px;background:#fff}.audit-card strong{display:block;font-size:24px;margin-top:6px}.audit-toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:15px 0}.audit-toolbar select,.audit-toolbar button{padding:9px 12px;border:1px solid #ccc;border-radius:7px;background:#fff}.audit-table{width:100%;border-collapse:collapse;background:#fff}.audit-table th,.audit-table td{padding:10px;border-bottom:1px solid #eee;text-align:left;font-size:13px}.badge{display:inline-block;padding:3px 8px;border-radius:999px;font-size:11px}.open{background:#fff1f0}.resolved{background:#f0fff4}.critical,.high{color:#b42318}.warning{color:#b54708}.muted2{color:#667085}.empty{text-align:center;padding:30px}.audit-scroll{overflow:auto}@media(max-width:800px){.audit-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.audit-table{min-width:900px}}
</style>
</head>
<body>
<main class="wrap" data-page="mikrotik-audit">
<h1>MikroTik Audit</h1><p class="muted">PPPoE enforcement and ERP ↔ MikroTik reconciliation</p>
<div class="audit-grid">
<div class="audit-card">Open findings<strong id="openCount">—</strong></div>
<div class="audit-card">Resolved findings<strong id="resolvedCount">—</strong></div>
<div class="audit-card">Enforcement success<strong id="successCount">—</strong></div>
<div class="audit-card">Enforcement failed<strong id="failedCount">—</strong></div>
</div>
<div class="audit-toolbar"><select id="status"><option value="">All status</option><option value="open">Open</option><option value="resolved">Resolved</option></select><select id="severity"><option value="">All severity</option><option value="critical">Critical</option><option value="high">High</option><option value="warning">Warning</option><option value="info">Info</option></select><button id="refresh">Refresh</button><span id="updated" class="muted2"></span></div>
<section class="audit-scroll"><table class="audit-table"><thead><tr><th>Router</th><th>Username</th><th>Finding</th><th>Severity</th><th>Status</th><th>Message</th><th>Last seen</th></tr></thead><tbody id="rows"><tr><td colspan="7" class="empty muted">Loading…</td></tr></tbody></table></section>
</main>
<script>
const $=id=>document.getElementById(id);
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
async function get(url){const r=await fetch(url,{credentials:'same-origin',headers:{Accept:'application/json'}});const j=await r.json();if(!r.ok)throw new Error(j.error?.message||'Request failed');return j;}
async function load(){
 $('rows').innerHTML='<tr><td colspan="7" class="empty muted">Loading…</td></tr>';
 try{
  const qs=new URLSearchParams();if($('status').value)qs.set('status',$('status').value);if($('severity').value)qs.set('severity',$('severity').value);
  const [findings,summary]=await Promise.all([get('/api/networking/mikrotik/audit?'+qs),get('/api/mikrotik/pppoe/enforcement-audit/summary')]);
  const data=findings.data||[], s=summary.data||{};
  $('openCount').textContent=data.filter(x=>x.status==='open').length;
  $('resolvedCount').textContent=data.filter(x=>x.status==='resolved').length;
  $('successCount').textContent=s.success??0;$('failedCount').textContent=s.failed??0;
  $('rows').innerHTML=data.length?data.map(x=>`<tr><td>${esc(x.router_id)}</td><td><strong>${esc(x.username)}</strong></td><td>${esc(x.finding_type)}</td><td><span class="badge ${esc(x.severity)}">${esc(x.severity)}</span></td><td><span class="badge ${esc(x.status)}">${esc(x.status)}</span></td><td>${esc(x.message)}</td><td>${esc(x.last_seen_at)}</td></tr>`).join(''):'<tr><td colspan="7" class="empty muted">No reconciliation findings.</td></tr>';
  $('updated').textContent='Updated '+new Date().toLocaleTimeString();
 }catch(e){$('rows').innerHTML=`<tr><td colspan="7" class="empty">${esc(e.message||e)}</td></tr>`;}
}
$('refresh').addEventListener('click',load);$('status').addEventListener('change',load);$('severity').addEventListener('change',load);load();
</script>
</body></html>
HTML);
    }
}
