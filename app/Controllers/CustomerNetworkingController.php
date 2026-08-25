<?php

declare(strict_types=1);

namespace Ispluka\Controllers;

use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Http\Response;
use Ispluka\Core\Security\Csrf;

final class CustomerNetworkingController
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly Csrf $csrf,
    ) {}

    public function page(): Response
    {
        $csrf = htmlspecialchars($this->csrf->token(), ENT_QUOTES, 'UTF-8');
        $body = <<<'HTML'
<section class="module-page" data-page="customer-networking">
    <div class="welcome-row">
        <div><span class="eyebrow">NETWORK</span><h1>Customer Networking</h1><p>Live PPPoE status and six-month usage for a customer.</p></div>
        <a class="secondary-action" href="/customers">Back to Customers</a>
    </div>
    <article class="panel">
        <div class="form-grid">
            <div><label for="customer_id">Customer ID</label><input id="customer_id" name="customer_id" type="number" min="1" placeholder="Customer ID"></div>
            <div><label for="router_id">Router ID</label><input id="router_id" name="router_id" type="number" min="1" placeholder="Router ID"></div>
            <div><label for="username">PPPoE Username</label><input id="username" name="username" placeholder="PPPoE username"></div>
        </div>
        <div class="button-row"><button type="button" data-load-customer>Load Customer</button><button type="button" class="secondary-action" data-live-btn>Live Status</button><button type="button" class="secondary-action" data-usage-btn>Usage History</button></div>
        <p class="error" data-error></p>
    </article>
    <section class="module-grid">
        <article class="panel"><div class="panel-head"><div><span class="panel-kicker">CUSTOMER</span><h2 data-name>Customer</h2><p class="muted">Code: <span data-code>—</span> · Phone: <span data-phone>—</span></p></div></div><div data-live>Load a customer and press Live Status.</div><a data-router hidden target="_blank" rel="noopener noreferrer" class="secondary-action">Open Customer Router</a></article>
        <article class="panel"><div class="panel-head"><div><span class="panel-kicker">USAGE</span><h2>Six-month history</h2></div></div><div data-usage><p>Press Usage History to load the report.</p></div></article>
    </section>
</section>
HTML;

        $script = <<<'JS'
<script>
(() => {
  const root=document.querySelector('[data-page="customer-networking"]'); if(!root)return;
  const q=s=>root.querySelector(s); const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
  const bytes=n=>{n=Number(n||0);const u=['B','KB','MB','GB','TB'];let i=0;while(n>=1024&&i<4){n/=1024;i++;}return `${n.toFixed(i?2:0)} ${u[i]}`;};
  const customerId=()=>q('[name="customer_id"]').value.trim();
  const network=()=>({router_id:q('[name="router_id"]').value.trim(),username:q('[name="username"]').value.trim()});
  const loadCustomer=async()=>{const id=customerId();if(!id){q('[data-error]').textContent='Customer ID is required.';return;}const r=await fetch(`/api/customer?id=${encodeURIComponent(id)}`,{credentials:'same-origin',headers:{Accept:'application/json'}});const j=await r.json();if(!r.ok)throw Error(j?.error?.message||'Customer not found.');const c=j.data||{};q('[data-name]').textContent=c.name||'Customer';q('[data-code]').textContent=c.customer_code||'—';q('[data-phone]').textContent=c.phone||'—';let m=c.metadata||{};if(typeof m==='string'){try{m=JSON.parse(m||'{}')}catch(_){m={}}}const n=m.network||m.mikrotik||{};q('[name="router_id"]').value=n.router_id||m.router_id||'';q('[name="username"]').value=n.username||m.pppoe_username||m.username||'';q('[data-error]').textContent='';};
  const live=async()=>{const {router_id,username}=network();if(!router_id||!username)throw Error('Router ID and PPPoE username are required.');const r=await fetch(`/api/networking/mikrotik/pppoe/live?router_id=${encodeURIComponent(router_id)}&username=${encodeURIComponent(username)}`,{credentials:'same-origin',headers:{Accept:'application/json'}});const j=await r.json();if(!r.ok)throw Error(j?.error?.message||'Live data unavailable.');const d=j.data||{};q('[data-live]').innerHTML=`<b>${d.online?'ONLINE':'OFFLINE'}</b><br>IP: ${esc(d.active_ip||'—')}<br>Rx: ${esc(d.rx_rate_bps??'—')} bps · Tx: ${esc(d.tx_rate_bps??'—')} bps<br>Uptime: ${esc(d.uptime_seconds??0)} sec<br>Last seen: ${esc(d.last_seen_at||'—')}`;const a=q('[data-router]');a.hidden=!d.active_ip;if(d.active_ip)a.href=`http://${d.active_ip}:8080/`;};
  const usage=async()=>{const {router_id,username}=network();if(!router_id||!username)throw Error('Router ID and PPPoE username are required.');const now=new Date(),from=new Date(now);from.setMonth(now.getMonth()-6);const p=new URLSearchParams({router_id,username,from:from.toISOString(),to:now.toISOString()});const r=await fetch(`/api/networking/mikrotik/pppoe/usage?${p}`,{credentials:'same-origin',headers:{Accept:'application/json'}});const j=await r.json();if(!r.ok)throw Error(j?.error?.message||'Usage unavailable.');const rows=j.data||[];q('[data-usage]').innerHTML=`<div class="table-wrap"><table><thead><tr><th>Month</th><th>Rx</th><th>Tx</th><th>Online</th></tr></thead><tbody>${rows.map(x=>`<tr><td>${esc(x.month_start)}</td><td>${bytes(x.rx_bytes)}</td><td>${bytes(x.tx_bytes)}</td><td>${Math.round(Number(x.online_seconds||0)/3600)} h</td></tr>`).join('')||'<tr><td colspan="4">No history.</td></tr>'}</tbody></table></div>`;};
  const run=(fn)=>fn().catch(e=>q('[data-error]').textContent=e.message);
  q('[data-load-customer]').addEventListener('click',()=>run(loadCustomer));q('[data-live-btn]').addEventListener('click',()=>run(live));q('[data-usage-btn]').addEventListener('click',()=>run(usage));
  const id=new URLSearchParams(location.search).get('customer_id');if(id){q('[name="customer_id"]').value=id;run(loadCustomer);}
})();
</script>
JS;

        return Response::text($this->layout($body, $csrf, $script));
    }

    private function layout(string $body, string $csrf, string $script): string
    {
        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/assets/css/app.css?v=8"><link rel="stylesheet" href="/assets/css/dashboard.css?v=19"><title>ISPLUKA — Customer Networking</title></head><body class="dashboard-page"><div class="app-shell"><header class="app-header dashboard-header"><div class="container header-inner"><button class="menu-toggle" type="button" data-menu-toggle>Menu</button><a class="brand" href="/">ISPLUKA</a><div class="header-tools"><a class="header-icon" href="/customers">Customers</a><form method="post" action="/logout"><input type="hidden" name="_csrf" value="'.$csrf.'"><button type="submit" class="logout-button">Logout</button></form></div></div></header><aside class="sidebar" data-sidebar><nav class="nav dashboard-nav"><a href="/">Dashboard</a><a href="/customers">Customers</a><a class="active" href="/networking/customer">Customer Networking</a><a href="/networking/mikrotik/routers">MikroTik Routers</a><a href="/networking/mikrotik/enforcement-audit">Network Audit</a><a href="/networking/hotspot">Hotspot</a><a href="/networking/olt">OLT</a></nav></aside><main class="main main-with-sidebar"><div class="container">'.$body.'</div></main></div><script src="/assets/js/app.js?v=8"></script>'.$script.'</body></html>';
    }
}
