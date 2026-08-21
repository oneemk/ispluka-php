<?php

declare(strict_types=1);

namespace Ispluka\Controllers;

use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Http\Response;
use Ispluka\Core\Security\Csrf;

final class NetworkModuleController
{
    public function __construct(private readonly AuthManager $auth, private readonly Csrf $csrf) {}

    public function hotspot(): Response
    {
        $token = htmlspecialchars($this->csrf->token(), ENT_QUOTES, 'UTF-8');
        return Response::text($this->layout('Hotspot', $token, <<<'HTML'
<section class="module-grid">
  <article class="panel"><div class="panel-head"><div><span class="panel-kicker">HOTSPOT</span><h2>Active Sessions</h2><p class="muted">Live MikroTik Hotspot sessions</p></div><button class="btn" data-sync>Sync</button></div><div class="table-wrap"><table><thead><tr><th>User</th><th>Address</th><th>MAC</th><th>Uptime</th><th>Router</th><th>Action</th></tr></thead><tbody data-sessions><tr><td colspan="6">Loading…</td></tr></tbody></table></div></article>
  <article class="panel"><div class="panel-head"><div><span class="panel-kicker">HOTSPOT</span><h2>Users</h2><p class="muted">Subscriber Hotspot accounts</p></div></div><div class="table-wrap"><table><thead><tr><th>User</th><th>Profile</th><th>Disabled</th><th>Router</th></tr></thead><tbody data-users><tr><td colspan="4">Loading…</td></tr></tbody></table></div></article>
  <article class="panel"><div class="panel-head"><div><span class="panel-kicker">HOTSPOT</span><h2>Profiles</h2><p class="muted">RouterOS Hotspot profiles</p></div></div><div class="table-wrap"><table><thead><tr><th>Name</th><th>Rate Limit</th><th>Shared Users</th><th>Router</th></tr></thead><tbody data-profiles><tr><td colspan="4">Loading…</td></tr></tbody></table></div></article>
  <article class="panel"><div class="panel-head"><div><span class="panel-kicker">HOTSPOT</span><h2>Operations</h2><p class="muted">Hosts, bindings, walled garden and logs</p></div></div><div class="shortcut-grid"><a class="shortcut" href="/networking/mikrotik/routers"><strong>Routers</strong><small>Choose RouterOS source</small></a><a class="shortcut" href="#" data-load="hosts"><strong>Hosts</strong><small>Connected Hotspot hosts</small></a><a class="shortcut" href="#" data-load="ip-bindings"><strong>IP Bindings</strong><small>Bypass and access rules</small></a><a class="shortcut" href="#" data-load="walled-garden"><strong>Walled Garden</strong><small>Allowed destinations</small></a><a class="shortcut" href="#" data-load="logs"><strong>Router Logs</strong><small>Hotspot events</small></a><a class="shortcut" href="#" data-load="traffic"><strong>Traffic</strong><small>Usage overview</small></a></div></article>
</section>
<script>
(() => {
 const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
 const render=(el,rows,cols)=>el.innerHTML=(Array.isArray(rows)&&rows.length?rows.map(r=>'<tr>'+cols.map(c=>'<td>'+esc(r[c])+'</td>').join('')+'</tr>').join(''):'<tr><td colspan="'+cols.length+'">No data</td></tr>');
 async function get(path){const r=await fetch(path,{credentials:'same-origin',headers:{Accept:'application/json'}});const j=await r.json();if(!r.ok)throw Error(j.error?.message||'Request failed');return j.data??[]}
 async function load(){try{render(document.querySelector('[data-sessions]'),await get('/api/hotspot/sessions'),['user','address','mac-address','uptime','router_id']);render(document.querySelector('[data-users]'),await get('/api/hotspot/users'),['name','profile','disabled','router_id']);render(document.querySelector('[data-profiles]'),await get('/api/hotspot/profiles'),['name','rate-limit','shared-users','router_id']);}catch(e){document.querySelectorAll('tbody').forEach(x=>x.innerHTML='<tr><td colspan="6">'+esc(e.message)+'</td></tr>')}}
document.querySelector('[data-sync]')?.addEventListener('click',load);load();
})();
</script>
HTML);
    }

    public function olt(): Response
    {
        return Response::text($this->layout('OLT', '', <<<'HTML'
<section class="module-grid">
 <article class="panel"><div class="panel-head"><div><span class="panel-kicker">NETWORK</span><h2>OLT Management</h2><p class="muted">Fiber access infrastructure</p></div><button class="btn" disabled>Coming next</button></div><div class="empty-state"><strong>OLT module ready</strong><p>OLT inventory, PON ports, ONUs/ONTs, optical power, alarms and customer mapping will be managed here.</p></div></article>
 <article class="panel"><div class="panel-head"><div><span class="panel-kicker">OLT</span><h2>Planned modules</h2></div></div><div class="shortcut-grid"><div class="shortcut"><strong>OLT Inventory</strong><small>Vendor, model, IP and status</small></div><div class="shortcut"><strong>PON / ONU</strong><small>Ports, ONUs and optical status</small></div><div class="shortcut"><strong>Provisioning</strong><small>Service/VLAN/customer mapping</small></div><div class="shortcut"><strong>Alarms</strong><small>LOS, dying gasp and power events</small></div></div></article>
</section>
HTML);
    }

    private function layout(string $title, string $csrf, string $body): string
    {
        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/assets/css/app.css?v=4"><link rel="stylesheet" href="/assets/css/dashboard.css?v=5"><title>ISPLUKA '.htmlspecialchars($title,ENT_QUOTES,'UTF-8').'</title></head><body class="dashboard-page"><div class="app-shell"><header class="app-header dashboard-header"><div class="container header-inner"><button class="menu-toggle" type="button" data-menu-toggle>☰</button><a class="brand" href="/">ISPLUKA</a><div class="header-tools"><div class="language-switch"><button type="button" data-language="en" class="language-btn active">EN</button><button type="button" data-language="bn" class="language-btn">বাংলা</button></div><a class="header-icon" href="/customers">♙</a><form method="post" action="/logout"><input type="hidden" name="_csrf" value="'.$csrf.'"><button type="submit" class="logout-button"><span data-i18n="logout">Logout</span></button></form></div></div></header><aside class="sidebar" data-sidebar><nav class="nav dashboard-nav"><a href="/"><span data-i18n="dashboard">Dashboard</span></a><div class="nav-section" data-i18n="network">Network</div><a href="/networking/mikrotik/routers"><span data-i18n="mikrotik_routers">MikroTik Routers</span></a><a href="/networking/hotspot"><span data-i18n="hotspot">Hotspot</span></a><a href="/networking/olt"><span data-i18n="olt">OLT</span></a><a href="/networking/mikrotik/enforcement-audit"><span data-i18n="network_audit">Network Audit</span></a></nav></aside><main class="main main-with-sidebar"><div class="container"><section class="welcome-row"><div><span class="eyebrow">NETWORK</span><h1>'.htmlspecialchars($title,ENT_QUOTES,'UTF-8').'</h1><p data-i18n="network_module_subtitle">Network infrastructure management.</p></div></section>'.$body.'</div></main></div><script src="/assets/js/app.js?v=6"></script></body></html>';
    }
}
