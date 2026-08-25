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
    ) {}

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
        <div><strong>Router time needs attention</strong><small data-hs-warning-text></small></div>
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
            <div class="hotspot-panel-head"><div><h2>Hotspot Operations</h2><small>Live RouterOS data</small></div><select class="hotspot-btn" data-hs-router><option value="">All routers</option></select></div>
            <div class="hotspot-tabs">
                <button class="active" type="button" data-hs-tab="sessions">Sessions</button><button type="button" data-hs-tab="users">Users</button><button type="button" data-hs-tab="profiles">Profiles</button><button type="button" data-hs-tab="bindings">IP Bindings</button><button type="button" data-hs-tab="hosts">Hosts</button><button type="button" data-hs-tab="walled">Walled Garden</button><button type="button" data-hs-tab="addresses">Address Lists</button><button type="button" data-hs-tab="traffic">Traffic</button><button type="button" data-hs-tab="history">Login History</button><button type="button" data-hs-tab="logs">Logs</button>
            </div>
            <div class="hotspot-table-wrap"><table class="hotspot-table"><thead data-hs-head></thead><tbody data-hs-body><tr><td class="hotspot-empty">Loading...</td></tr></tbody></table></div>
        </article>
        <aside class="hotspot-panel">
            <div class="hotspot-panel-head"><div><h2>Hotspot Servers</h2><small>Router availability and clock pre-flight</small></div></div>
            <div class="hotspot-side-list" data-hs-routers><div class="hotspot-empty">Loading...</div></div>
        </aside>
    </section>
</div>
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
        $hotspot = $title === 'Hotspot';
        $extra = $hotspot ? '<link rel="stylesheet" href="/assets/css/hotspot.css?v=2"><meta name="csrf-token" content="'.$csrf.'">' : '';
        $script = $hotspot ? '<script src="/assets/js/hotspot.js?v=2"></script>' : '';
        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<link rel="stylesheet" href="/assets/css/app.css?v=8"><link rel="stylesheet" href="/assets/css/dashboard.css?v=19">'.$extra
            . '<title>ISPLUKA — '.$safeTitle.'</title></head><body class="dashboard-page '.($hotspot?'hotspot-page':'').'">'
            . '<div class="app-shell"><header class="app-header dashboard-header"><div class="container header-inner"><button class="menu-toggle" type="button" data-menu-toggle>Menu</button><a class="brand" href="/">ISPLUKA</a><div class="header-tools"><div class="language-switch"><button type="button" data-language="en" class="language-btn active">EN</button><button type="button" data-language="bn" class="language-btn">বাংলা</button></div><a class="header-icon" href="/customers">Profile</a><form method="post" action="/logout"><input type="hidden" name="_csrf" value="'.$csrf.'"><button type="submit" class="logout-button"><span data-i18n="logout">Logout</span></button></form></div></div></header>'
            . '<aside class="sidebar" data-sidebar><nav class="nav dashboard-nav"><a href="/">Dashboard</a><a href="/networking/mikrotik/routers">MikroTik Routers</a><a class="'.($hotspot?'active':'').'" href="/networking/hotspot">Hotspot</a><a href="/networking/olt">OLT</a><a href="/networking/mikrotik/enforcement-audit">Network Audit</a></nav></aside>'
            . '<main class="main main-with-sidebar"><div class="container"><section class="welcome-row"><div><span class="eyebrow">NETWORK</span><h1>'.$safeTitle.'</h1><p>Network infrastructure management.</p></div></section>'.$body.'</div></main></div><script src="/assets/js/app.js?v=8"></script>'.$script.'</body></html>';
    }
}
