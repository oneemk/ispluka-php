<?php

declare(strict_types=1);

namespace Ispluka\Controllers;

use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Http\Response;
use Ispluka\Core\Security\Csrf;

final class NetworkModuleController
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly Csrf $csrf,
    ) {
    }

    public function hotspot(): Response
    {
        $csrf = htmlspecialchars($this->csrf->token(), ENT_QUOTES, 'UTF-8');
        $body = <<<'HTML'
<div class="hotspot-wrap">
    <section class="hotspot-hero">
        <div><h1>Hotspot</h1><p>MikroTik Hotspot operations, users, validity, sessions and router resources.</p></div>
        <div class="hotspot-toolbar"><button class="hotspot-btn primary" type="button" data-hs-refresh>Refresh</button><button class="hotspot-btn" type="button" data-hs-sync>Sync Sessions</button></div>
    </section>

    <section class="hotspot-warning" data-hs-warning>
        <div><strong data-hs-warning-title>Router time needs attention</strong><small data-hs-warning-text></small></div>
        <div class="hotspot-actions"><button class="hotspot-btn primary" type="button" data-hs-fix>Fix Time</button><button class="hotspot-btn" type="button" data-hs-ignore>Ignore & Continue</button></div>
    </section>

    <section class="hotspot-grid">
        <article class="hotspot-card"><span>Hotspot Servers</span><strong data-hs-count="routers">—</strong></article>
        <article class="hotspot-card"><span>Active Users</span><strong data-hs-count="active">—</strong></article>
        <article class="hotspot-card"><span>Sessions</span><strong data-hs-count="sessions">—</strong></article>
        <article class="hotspot-card"><span>Hotspot Users</span><strong data-hs-count="users">—</strong></article>
    </section>

    <section class="hotspot-layout">
        <article class="hotspot-panel">
            <div class="hotspot-panel-head"><div><h2 data-hs-title>Active Users / Sessions</h2><small data-hs-subtitle>Live RouterOS snapshot</small></div><select class="hotspot-btn" data-hs-router><option value="">All routers</option></select></div>
            <div class="hotspot-tabs">
                <button class="active" type="button" data-hs-tab="sessions">Sessions</button>
                <button type="button" data-hs-tab="users">Users</button>
                <button type="button" data-hs-tab="profiles">Profiles</button>
                <button type="button" data-hs-tab="bindings">IP Bindings</button>
                <button type="button" data-hs-tab="hosts">Hosts</button>
                <button type="button" data-hs-tab="walled">Walled Garden</button>
                <button type="button" data-hs-tab="addresses">Address Lists</button>
                <button type="button" data-hs-tab="traffic">Traffic</button>
                <button type="button" data-hs-tab="history">Login History</button>
                <button type="button" data-hs-tab="logs">Logs</button>
            </div>
            <div class="hotspot-table-wrap"><table class="hotspot-table"><thead data-hs-head></thead><tbody data-hs-body><tr><td class="hotspot-empty">Loading...</td></tr></tbody></table></div>
        </article>

        <aside class="hotspot-panel">
            <div class="hotspot-panel-head"><div><h2>Hotspot Servers</h2><small>Router availability and clock pre-flight</small></div></div>
            <div class="hotspot-side-list" data-hs-routers><div class="hotspot-empty">Loading...</div></div>
        </aside>
    </section>
</div>
<script>
(function(){
    const csrf = <?=json_encode($csrf)?>;
    const state = {tab:'sessions',router:'',routers:[],data:{}};
    const $ = s => document.querySelector(s);
    const $$ = s => Array.from(document.querySelectorAll(s));
    const esc = v => String(v ?? '').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    const val = (row, keys) => { for(const k of keys){ if(row && row[k] !== undefined && row[k] !== null) return row[k]; } return ''; };
    const post = async(url, data={}, method='POST') => { const p=new URLSearchParams({_csrf:csrf}); Object.entries(data).forEach(([k,v])=>p.set(k,String(v))); const r=await fetch(url,{method,credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json'},body:p}); const j=await r.json().catch(()=>({})); if(!r.ok) throw new Error(j?.error?.message||'Request failed'); return j.data??j; };
    const get = async(url) => { const r=await fetch(url,{credentials:'same-origin',headers:{Accept:'application/json'}}); const j=await r.json().catch(()=>({})); if(!r.ok) throw new Error(j?.error?.message||'Request failed'); return j.data??[]; };
    const setCount=(k,v)=>{const e=document.querySelector('[data-hs-count="'+k+'"]');if(e)e.textContent=Array.isArray(v)?v.length:(v??0)};
    const columns={
      sessions:[['User','user','name'],['Address','address'],['MAC','mac-address','mac_address'],['Uptime','uptime'],['Router','router_id'],['Action','_action']],
      users:[['Username','name','username'],['Profile','profile','router_profile'],['Status','status','disabled'],['Activated','activated_at'],['Expires','expires_at'],['Router','router_id']],
      profiles:[['Name','name'],['Code','code'],['Duration','duration_expression','duration'],['Rate Limit','rate-limit','rate_limit'],['Data Limit','data_limit'],['Shared Users','shared-users','shared_users'],['Status','status']],
      bindings:[['Address','address'],['MAC','mac-address','mac_address'],['Type','type'],['Comment','comment'],['Router','router_id']],
      hosts:[['Address','address'],['MAC','mac-address','mac_address'],['To Address','to-address','to_address'],['Uptime','uptime'],['Router','router_id']],
      walled:[['Server','server'],['Dst Host','dst-host','dst_host'],['Action','action'],['Comment','comment'],['Router','router_id']],
      addresses:[['List','list'],['Address','address'],['Timeout','timeout'],['Comment','comment'],['Router','router_id']],
      traffic:[['User','user','name'],['Rx','rx','rx_bytes'],['Tx','tx','tx_bytes'],['Uptime','uptime'],['Router','router_id']],
      history:[['User','user','name'],['Address','address'],['MAC','mac-address','mac_address'],['Login','login_time','time'],['Logout','logout_time'],['Router','router_id']],
      logs:[['Time','time','timestamp'],['Topics','topics'],['Message','message'],['Router','router_id']]
    };
    const endpoints={sessions:'/api/hotspot/sessions',users:'/api/hotspot/users',profiles:'/api/hotspot/profiles',bindings:'/api/hotspot/ip-bindings',hosts:'/api/hotspot/hosts',walled:'/api/hotspot/walled-garden',addresses:'/api/hotspot/address-lists',traffic:'/api/hotspot/traffic',history:'/api/hotspot/login-history',logs:'/api/hotspot/logs'};
    function render(){
      const head=$('[data-hs-head]'), body=$('[data-hs-body]'), defs=columns[state.tab];
      head.innerHTML='<tr>'+defs.map(d=>'<th>'+esc(d[0])+'</th>').join('')+'</tr>';
      const rows=state.data[state.tab]||[];
      if(!rows.length){body.innerHTML='<tr><td class="hotspot-empty" colspan="'+defs.length+'">No data available.</td></tr>';return;}
      body.innerHTML=rows.map(row=>'<tr>'+defs.map(d=>{if(d[1]==='_action')return '<td><button class="hotspot-btn" type="button" data-disconnect="'+esc(val(row,['id','session_id']))+'">Disconnect</button></td>';return '<td>'+esc(val(row,d.slice(1)))+'</td>'}).join('')+'</tr>').join('');
      $$('[data-disconnect]').forEach(b=>b.addEventListener('click',async()=>{try{await post('/api/hotspot/sessions/disconnect',{id:b.dataset.disconnect});await loadTab()}catch(e){alert(e.message)}}));
    }
    async function loadTab(){let url=endpoints[state.tab]; if(state.router && !['history','logs'].includes(state.tab)) url+=(url.includes('?')?'&':'?')+'router_id='+encodeURIComponent(state.router); try{state.data[state.tab]=await get(url);render(); if(state.tab==='sessions')setCount('sessions',state.data.sessions); if(state.tab==='users')setCount('users',state.data.users)}catch(e){$('[data-hs-body]').innerHTML='<tr><td class="hotspot-empty" colspan="8">'+esc(e.message)+'</td></tr>'}}
    async function loadRouters(){try{state.routers=await get('/api/networking/mikrotik/routers');const select=$('[data-hs-router]');select.innerHTML='<option value="">All routers</option>'+state.routers.map(r=>'<option value="'+esc(val(r,['id','router_id']))+'">'+esc(val(r,['name','router_name','host','ip']))+'</option>').join('');select.value=state.router;setCount('routers',state.routers);const box=$('[data-hs-routers]');box.innerHTML=state.routers.length?state.routers.map(r=>'<button type="button" class="hotspot-side-item" data-router="'+esc(val(r,['id','router_id']))+'"><span><strong>'+esc(val(r,['name','router_name','host','ip']))+'</strong><small>'+esc(val(r,['ip','host','address']))+'</small></span><span class="hotspot-status">'+esc(val(r,['status','connection_status'])||'Unknown')+'</span></button>').join(''):'<div class="hotspot-empty">No routers found.</div>';$$('[data-router]').forEach(b=>b.addEventListener('click',()=>{state.router=b.dataset.router;select.value=state.router;checkTime();loadTab()})); if(state.routers[0])checkTime()}catch(e){$('[data-hs-routers]').innerHTML='<div class="hotspot-empty">'+esc(e.message)+'</div>'}}
    async function checkTime(){if(!state.router)return;try{const d=await get('/api/hotspot/routers/time-check?router_id='+encodeURIComponent(state.router));const warning=d?.warning??d?.warning_state??false; if(warning){$('[data-hs-warning]').classList.add('show');$('[data-hs-warning-text]').textContent='Router: '+(d.router_time||d.routerTime||'—')+' · Server: '+(d.server_time||d.serverTime||'—')+' · Difference: '+(d.difference_seconds??d.difference??'—')+'s';}else{$('[data-hs-warning]').classList.remove('show')}}catch(e){}}
    $$('[data-hs-tab]').forEach(b=>b.addEventListener('click',()=>{$$('[data-hs-tab]').forEach(x=>x.classList.remove('active'));b.classList.add('active');state.tab=b.dataset.hsTab;loadTab()}));
    $('[data-hs-router]').addEventListener('change',e=>{state.router=e.target.value;checkTime();loadTab()});
    $('[data-hs-refresh]').addEventListener('click',async()=>{await loadRouters();await loadTab()});
    $('[data-hs-sync]').addEventListener('click',async()=>{if(!state.router){alert('Select a router first.');return}try{await post('/api/hotspot/sessions/sync',{router_id:state.router});await loadTab()}catch(e){alert(e.message)}});
    $('[data-hs-ignore]').addEventListener('click',()=>{$('[data-hs-warning]').classList.remove('show')});
    $('[data-hs-fix]').addEventListener('click',async()=>{if(!state.router)return;try{await get('/api/hotspot/routers/time-check?router_id='+encodeURIComponent(state.router));alert('Time correction requires explicit router-side implementation; no silent change was performed.')}catch(e){alert(e.message)}});
    (async()=>{await loadRouters();setCount('active',0);await loadTab();try{const u=await get('/api/hotspot/users');setCount('users',u);state.data.users=u}catch(e){}try{const s=await get('/api/hotspot/sessions');setCount('sessions',s);state.data.sessions=s;render()}catch(e){}})();
}());
</script>
HTML;
        return Response::text($this->layout('Hotspot', $body, $csrf));
    }

    public function olt(): Response
    {
        $body = <<<'HTML'
<section class="module-grid">
    <article class="panel"><div class="panel-head"><div><span class="panel-kicker">NETWORK</span><h2>OLT Management</h2><p class="muted">Fiber access infrastructure</p></div></div><div class="empty-state"><strong>OLT module ready</strong><p>OLT inventory, PON ports, ONUs/ONTs, optical power, alarms and customer mapping will be managed here.</p></div></article>
    <article class="panel"><div class="panel-head"><div><span class="panel-kicker">OLT</span><h2>Modules</h2></div></div><div class="shortcut-grid"><div class="shortcut"><strong>OLT Inventory</strong><small>Vendor, model, IP and status</small></div><div class="shortcut"><strong>PON / ONU</strong><small>Ports, ONUs and optical status</small></div><div class="shortcut"><strong>Provisioning</strong><small>Service, VLAN and customer mapping</small></div><div class="shortcut"><strong>Alarms</strong><small>LOS, dying gasp and power events</small></div></div></article>
</section>
HTML;
        return Response::text($this->layout('OLT', $body, htmlspecialchars($this->csrf->token(), ENT_QUOTES, 'UTF-8')));
    }

    private function layout(string $title, string $body, string $csrf = ''): string
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $extra = $title === 'Hotspot' ? '<link rel="stylesheet" href="/assets/css/hotspot.css?v=1">' : '';
        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<link rel="stylesheet" href="/assets/css/app.css?v=8"><link rel="stylesheet" href="/assets/css/dashboard.css?v=19">'.$extra
            . '<title>ISPLUKA — '.$safeTitle.'</title></head><body class="dashboard-page '.($title==='Hotspot'?'hotspot-page':'').'">'
            . '<div class="app-shell"><header class="app-header dashboard-header"><div class="container header-inner"><button class="menu-toggle" type="button" data-menu-toggle>Menu</button><a class="brand" href="/">ISPLUKA</a><div class="header-tools"><div class="language-switch"><button type="button" data-language="en" class="language-btn active">EN</button><button type="button" data-language="bn" class="language-btn">বাংলা</button></div><a class="header-icon" href="/customers">Profile</a><form method="post" action="/logout"><input type="hidden" name="_csrf" value="'.$csrf.'"><button type="submit" class="logout-button"><span data-i18n="logout">Logout</span></button></form></div></div></header>'
            . '<aside class="sidebar" data-sidebar><nav class="nav dashboard-nav"><a href="/">Dashboard</a><a href="/networking/mikrotik/routers">MikroTik Routers</a><a class="active" href="/networking/hotspot">Hotspot</a><a href="/networking/olt">OLT</a><a href="/networking/mikrotik/enforcement-audit">Network Audit</a></nav></aside>'
            . '<main class="main main-with-sidebar"><div class="container"><section class="welcome-row"><div><span class="eyebrow">NETWORK</span><h1>'.$safeTitle.'</h1><p>Network infrastructure management.</p></div></section>'.$body.'</div></main></div><script src="/assets/js/app.js?v=8"></script></body></html>';
    }
}
