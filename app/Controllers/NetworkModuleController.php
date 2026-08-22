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
        $body = <<<'HTML'
<section class="module-grid">
    <article class="panel">
        <div class="panel-head">
            <div><span class="panel-kicker">HOTSPOT</span><h2>Active Sessions</h2><p class="muted">Live MikroTik Hotspot sessions</p></div>
            <button class="btn" type="button" data-sync>Sync</button>
        </div>
        <div class="table-wrap"><table><thead><tr><th>User</th><th>Address</th><th>MAC</th><th>Uptime</th><th>Router</th><th>Action</th></tr></thead><tbody data-sessions><tr><td colspan="6">Loading...</td></tr></tbody></table></div>
    </article>
    <article class="panel">
        <div class="panel-head"><div><span class="panel-kicker">HOTSPOT</span><h2>Users</h2><p class="muted">Subscriber Hotspot accounts</p></div></div>
        <div class="table-wrap"><table><thead><tr><th>User</th><th>Profile</th><th>Disabled</th><th>Router</th></tr></thead><tbody data-users><tr><td colspan="4">Loading...</td></tr></tbody></table></div>
    </article>
    <article class="panel">
        <div class="panel-head"><div><span class="panel-kicker">HOTSPOT</span><h2>Profiles</h2><p class="muted">RouterOS Hotspot profiles</p></div></div>
        <div class="table-wrap"><table><thead><tr><th>Name</th><th>Rate Limit</th><th>Shared Users</th><th>Router</th></tr></thead><tbody data-profiles><tr><td colspan="4">Loading...</td></tr></tbody></table></div>
    </article>
</section>
<script>
(function () {
    function esc(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
            var map = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'};
            if (c === "'") return '&#039;';
            return map[c] || c;
        });
    }
    function render(el, rows, columns) {
        if (!el) return;
        if (!Array.isArray(rows) || rows.length === 0) {
            el.innerHTML = '<tr><td colspan="' + columns.length + '">No data</td></tr>';
            return;
        }
        el.innerHTML = rows.map(function (row) {
            return '<tr>' + columns.map(function (column) { return '<td>' + esc(row[column]) + '</td>'; }).join('') + '</tr>';
        }).join('');
    }
    async function getJson(url) {
        var response = await fetch(url, {credentials:'same-origin', headers:{Accept:'application/json'}});
        var json = await response.json();
        if (!response.ok) throw new Error((json.error && json.error.message) || 'Request failed');
        return json.data || [];
    }
    async function load() {
        try {
            render(document.querySelector('[data-sessions]'), await getJson('/api/hotspot/sessions'), ['user','address','mac-address','uptime','router_id']);
            render(document.querySelector('[data-users]'), await getJson('/api/hotspot/users'), ['name','profile','disabled','router_id']);
            render(document.querySelector('[data-profiles]'), await getJson('/api/hotspot/profiles'), ['name','rate-limit','shared-users','router_id']);
        } catch (error) {
            document.querySelectorAll('tbody').forEach(function (tbody) { tbody.innerHTML = '<tr><td colspan="6">' + esc(error.message) + '</td></tr>'; });
        }
    }
    var sync = document.querySelector('[data-sync]');
    if (sync) sync.addEventListener('click', load);
    load();
}());
</script>
HTML;

        return Response::text($this->layout('Hotspot', $body));
    }

    public function olt(): Response
    {
        $body = <<<'HTML'
<section class="module-grid">
    <article class="panel">
        <div class="panel-head"><div><span class="panel-kicker">NETWORK</span><h2>OLT Management</h2><p class="muted">Fiber access infrastructure</p></div></div>
        <div class="empty-state"><strong>OLT module ready</strong><p>OLT inventory, PON ports, ONUs/ONTs, optical power, alarms and customer mapping will be managed here.</p></div>
    </article>
    <article class="panel">
        <div class="panel-head"><div><span class="panel-kicker">OLT</span><h2>Modules</h2></div></div>
        <div class="shortcut-grid">
            <div class="shortcut"><strong>OLT Inventory</strong><small>Vendor, model, IP and status</small></div>
            <div class="shortcut"><strong>PON / ONU</strong><small>Ports, ONUs and optical status</small></div>
            <div class="shortcut"><strong>Provisioning</strong><small>Service, VLAN and customer mapping</small></div>
            <div class="shortcut"><strong>Alarms</strong><small>LOS, dying gasp and power events</small></div>
        </div>
    </article>
</section>
HTML;

        return Response::text($this->layout('OLT', $body));
    }

    private function layout(string $title, string $body): string
    {
        $csrf = htmlspecialchars($this->csrf->token(), ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

        return '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<link rel="stylesheet" href="/assets/css/app.css?v=4">'
            . '<link rel="stylesheet" href="/assets/css/dashboard.css?v=5">'
            . '<title>ISPLUKA ' . $safeTitle . '</title></head><body class="dashboard-page">'
            . '<div class="app-shell"><header class="app-header dashboard-header"><div class="container header-inner">'
            . '<button class="menu-toggle" type="button" data-menu-toggle>☰</button><a class="brand" href="/">ISPLUKA</a>'
            . '<div class="header-tools"><div class="language-switch"><button type="button" data-language="en" class="language-btn active">EN</button><button type="button" data-language="bn" class="language-btn">বাংলা</button></div>'
            . '<a class="header-icon" href="/customers">♙</a><form method="post" action="/logout"><input type="hidden" name="_csrf" value="' . $csrf . '"><button type="submit" class="logout-button"><span data-i18n="logout">Logout</span></button></form></div></div></header>'
            . '<aside class="sidebar" data-sidebar><nav class="nav dashboard-nav"><a href="/"><span data-i18n="dashboard">Dashboard</span></a><a href="/networking/mikrotik/routers"><span data-i18n="mikrotik_routers">MikroTik Routers</span></a><a href="/networking/hotspot"><span data-i18n="hotspot">Hotspot</span></a><a href="/networking/olt"><span data-i18n="olt">OLT</span></a><a href="/networking/mikrotik/enforcement-audit"><span data-i18n="network_audit">Network Audit</span></a></nav></aside>'
            . '<main class="main main-with-sidebar"><div class="container"><section class="welcome-row"><div><span class="eyebrow">NETWORK</span><h1>' . $safeTitle . '</h1><p data-i18n="network_module_subtitle">Network infrastructure management.</p></div></section>' . $body . '</div></main></div>'
            . '<script src="/assets/js/app.js?v=6"></script></body></html>';
    }
}
