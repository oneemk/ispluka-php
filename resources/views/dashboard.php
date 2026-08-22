<?php
$summary = is_array($snapshot['summary'] ?? null) ? $snapshot['summary'] : [];
$payments = is_array($snapshot['recentPayments'] ?? null) ? $snapshot['recentPayments'] : [];
$customers = is_array($snapshot['recentCustomers'] ?? null) ? $snapshot['recentCustomers'] : [];
$trend = is_array($snapshot['collectionTrend'] ?? null) ? $snapshot['collectionTrend'] : [];
$money = static fn(float|int $v): string => '৳' . number_format((float) $v, 0);
$number = static fn(float|int $v): string => number_format((float) $v);
$maxTrend = max(1.0, ...array_map(static fn(array $r): float => (float) ($r['amount'] ?? 0), $trend ?: [['amount' => 1]]));
$csrfToken = htmlspecialchars((string) ($csrfToken ?? $csrf ?? ''), ENT_QUOTES, 'UTF-8');
$roleCode = (string) ($role ?? 'user');
$displayRole = ucwords(str_replace('_', ' ', $roleCode));
$greeting = in_array($roleCode, ['master_admin', 'admin'], true) ? 'Admin' : $displayRole;
$routerTotal = (int) ($summary['routers_total'] ?? 0);
$routerOnline = (int) ($summary['routers_online'] ?? 0);
$routerOffline = (int) ($summary['routers_offline'] ?? 0);
?>
<!doctype html>
<html lang="en" data-lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="theme-color" content="#07111f">
    <link rel="stylesheet" href="/assets/css/app.css?v=6">
    <link rel="stylesheet" href="/assets/css/dashboard.css?v=10">
    <title>ISPLUKA — Dashboard</title>
</head>
<body class="dashboard-page">
<div class="app-shell">
<header class="app-header dashboard-header">
    <div class="container header-inner">
        <button class="menu-toggle" type="button" data-menu-toggle aria-label="Open navigation" aria-expanded="false">☰</button>
        <a class="brand" href="/" aria-label="ISPLUKA">ISPLUKA</a>
        <div class="header-tools">
            <label class="global-search" aria-label="Search">
                <span aria-hidden="true">⌕</span>
                <input type="search" data-search placeholder="Search customer, phone or code…" autocomplete="off">
            </label>
            <div class="language-switch" role="group" aria-label="Language">
                <button type="button" data-language="en" class="language-btn active" aria-pressed="true">EN</button>
                <button type="button" data-language="bn" class="language-btn" aria-pressed="false">বাংলা</button>
            </div>
            <a class="header-icon" href="/customers" aria-label="Customers">♙</a>
            <form method="post" action="/logout" class="logout-form">
                <input type="hidden" name="_csrf" value="<?=$csrfToken?>">
                <button type="submit" class="logout-button">↪ <span data-i18n="logout">Logout</span></button>
            </form>
        </div>
    </div>
</header>

<aside class="sidebar" data-sidebar>
<nav class="nav dashboard-nav" aria-label="Primary navigation">
    <div class="nav-brand-mini"><span>IS</span><div><strong>ISPLUKA</strong><small>ISP ERP</small></div></div>
    <a class="active" href="/"><span class="nav-mark">⌂</span><span data-i18n="dashboard">Dashboard</span></a>

    <div class="nav-section" data-i18n="customers_billing">Customers & Billing</div>
    <a href="/customers"><span class="nav-mark">♙</span><span data-i18n="customers">Customers</span></a>
    <a href="/customers/create"><span class="nav-mark">＋</span><span data-i18n="add_customer">Add Customer</span></a>
    <a href="/collection"><span class="nav-mark">৳</span><span data-i18n="collection">Collection</span></a>
    <a href="/reports/collection"><span class="nav-mark">▤</span><span data-i18n="collection_report">Collection Report</span></a>

    <div class="nav-section" data-i18n="network">Network</div>
    <a href="/networking/mikrotik/routers"><span class="nav-mark">◉</span><span data-i18n="mikrotik_routers">MikroTik Routers</span></a>
    <a href="/networking/olt"><span class="nav-mark">▥</span><span data-i18n="olt">OLT</span></a>
    <a href="/networking/customer"><span class="nav-mark">⌁</span><span data-i18n="customer_networking">Customer Networking</span></a>
    <a href="/networking/mikrotik/enforcement-audit"><span class="nav-mark">✓</span><span data-i18n="network_audit">Enforcement Audit</span></a>

    <a class="nav-standalone" href="/networking/hotspot"><span class="nav-mark">◌</span><span data-i18n="hotspot">Hotspot</span></a>

    <div class="nav-section" data-i18n="management">Management</div>
    <a href="/subscription"><span class="nav-mark">◇</span><span data-i18n="subscription">Subscription</span></a>
    <?php if ($roleCode === 'master_admin'): ?>
        <a href="/admin/tenants"><span class="nav-mark">◆</span><span data-i18n="tenants_admins">Tenants / Admins</span></a>
        <a href="/admin/subscriptions"><span class="nav-mark">৳</span><span data-i18n="platform_billing">Platform Billing</span></a>
    <?php endif; ?>

    <form method="post" action="/logout" class="sidebar-logout-form">
        <input type="hidden" name="_csrf" value="<?=$csrfToken?>">
        <button type="submit"><span class="nav-mark">↪</span><span data-i18n="logout">Logout</span></button>
    </form>
</nav>
</aside>

<main class="main main-with-sidebar dashboard-main">
<div class="container dashboard-container">
    <section class="hero-row">
        <div>
            <span class="eyebrow" data-i18n="control_center">ISP CONTROL CENTER</span>
            <h1><span data-i18n="good_morning">Good Morning</span>, <?=htmlspecialchars($greeting, ENT_QUOTES, 'UTF-8')?> <span aria-hidden="true">👋</span></h1>
            <p><?=htmlspecialchars((string) $tenantName, ENT_QUOTES, 'UTF-8')?> · <span data-i18n="overview_subtitle">Your ISP operational overview at a glance.</span></p>
        </div>
        <div class="hero-right">
            <span class="scope-pill"><?=htmlspecialchars($displayRole, ENT_QUOTES, 'UTF-8')?></span>
            <a class="hero-action" href="/customers/create"><span>＋</span><span data-i18n="new_customer">New Customer</span></a>
        </div>
    </section>

    <section class="kpi-grid" aria-label="Business overview">
        <a class="kpi-card kpi-blue" href="/customers"><span class="kpi-label" data-i18n="total_customers">Total Customers</span><strong><?=$number($summary['customers'] ?? 0)?></strong><small>+<?=$number($summary['new_customers_today'] ?? 0)?> <span data-i18n="today">today</span></small></a>
        <a class="kpi-card kpi-green" href="/customers"><span class="kpi-label" data-i18n="active_services">Active Services</span><strong><?=$number($summary['active_services'] ?? 0)?></strong><small data-i18n="currently_active">Currently active</small></a>
        <a class="kpi-card kpi-amber" href="/customers"><span class="kpi-label" data-i18n="suspended_services">Suspended Services</span><strong><?=$number($summary['suspended_services'] ?? 0)?></strong><small data-i18n="service_status">Service status</small></a>
        <a class="kpi-card kpi-red" href="/reports/collection"><span class="kpi-label" data-i18n="overdue_invoices_count">Overdue Invoices</span><strong><?=$number($summary['overdue_invoices'] ?? 0)?></strong><small data-i18n="needs_attention">Needs attention</small></a>
        <a class="kpi-card kpi-violet" href="/reports/collection"><span class="kpi-label" data-i18n="outstanding">Due / Outstanding</span><strong><?=$money($summary['outstanding'] ?? 0)?></strong><small data-i18n="receivable">Current receivable</small></a>
        <a class="kpi-card kpi-cyan" href="/collection"><span class="kpi-label" data-i18n="today_collection">Today's Collection</span><strong><?=$money($summary['today_collected'] ?? 0)?></strong><small data-i18n="completed_today">Completed today</small></a>
        <a class="kpi-card kpi-indigo" href="/reports/collection"><span class="kpi-label" data-i18n="monthly_collected">Monthly Collection</span><strong><?=$money($summary['monthly_collected'] ?? 0)?></strong><small data-i18n="current_month">Current month</small></a>
        <a class="kpi-card kpi-slate" href="/networking/mikrotik/routers"><span class="kpi-label" data-i18n="routers">MikroTik Routers</span><strong><?=$number($routerTotal)?></strong><small><span class="online-text"><?=$number($routerOnline)?> <span data-i18n="online">online</span></span> · <span class="offline-text"><?=$number($routerOffline)?> <span data-i18n="offline">offline</span></span></small></a>
    </section>

    <section class="section-title">
        <div><span class="panel-kicker" data-i18n="network">NETWORK</span><h2 data-i18n="network_health">Network Health</h2></div>
        <a href="/networking/mikrotik/routers" data-i18n="manage">Manage Network →</a>
    </section>
    <section class="network-overview">
        <div class="network-main-card">
            <div class="network-main-head"><div><span class="live-dot"></span><span data-i18n="router_status">Router Status</span></div><a href="/networking/mikrotik/routers" data-i18n="refresh_status">View live status →</a></div>
            <div class="network-big"><strong><?=$number($routerOnline)?></strong><span data-i18n="online_routers">Online Routers</span></div>
            <div class="network-progress"><span style="width:<?= $routerTotal > 0 ? min(100, ($routerOnline / $routerTotal) * 100) : 0 ?>%"></span></div>
            <div class="network-foot"><span><b class="online-text">● <?=$number($routerOnline)?></b> <span data-i18n="online">online</span></span><span><b class="offline-text">● <?=$number($routerOffline)?></b> <span data-i18n="offline">offline</span></span><span><b><?=$number($routerTotal)?></b> <span data-i18n="total_routers">total</span></span></div>
        </div>
        <a class="network-module" href="/networking/mikrotik/routers"><span class="module-icon blue-module">◉</span><span><strong data-i18n="mikrotik_routers">MikroTik Routers</strong><small data-i18n="manage_network">Router management & PPPoE</small></span><b>→</b></a>
        <a class="network-module" href="/networking/olt"><span class="module-icon violet-module">▥</span><span><strong data-i18n="olt">OLT</strong><small data-i18n="olt_subtitle">Optical line terminal management</small></span><b>→</b></a>
        <a class="network-module" href="/networking/hotspot"><span class="module-icon cyan-module">◌</span><span><strong data-i18n="hotspot">Hotspot</strong><small data-i18n="hotspot_subtitle">Users, profiles & sessions</small></span><b>→</b></a>
        <a class="network-module" href="/networking/mikrotik/enforcement-audit"><span class="module-icon amber-module">✓</span><span><strong data-i18n="network_audit">Enforcement Audit</strong><small data-i18n="audit_subtitle">PPPoE reconciliation & audit</small></span><b>→</b></a>
    </section>

    <section class="dashboard-columns">
        <article class="panel collection-panel">
            <div class="panel-head"><div><span class="panel-kicker" data-i18n="financial">FINANCIAL</span><h2 data-i18n="collection_overview">Collection Overview</h2><p class="muted" data-i18n="last_six_months">Completed payments · last 6 months</p></div><a href="/reports/collection" data-i18n="view_report">View report →</a></div>
            <div class="trend-chart">
                <?php foreach ($trend as $row): $height = max(7, min(100, ((float) ($row['amount'] ?? 0) / $maxTrend) * 100)); ?>
                    <div class="trend-item"><div class="trend-value"><?=$money((float) ($row['amount'] ?? 0))?></div><div class="trend-bar-wrap"><div class="trend-bar" style="height:<?=$height?>%"></div></div><span><?=htmlspecialchars((string) ($row['label'] ?? ''), ENT_QUOTES, 'UTF-8')?></span></div>
                <?php endforeach; ?>
                <?php if ($trend === []): ?><div class="empty-state" data-i18n="no_collection">Collection history will appear here after completed payments are recorded.</div><?php endif; ?>
            </div>
        </article>
        <article class="panel service-panel">
            <div class="panel-head"><div><span class="panel-kicker" data-i18n="services">SERVICES</span><h2 data-i18n="service_health">Service Health</h2><p class="muted" data-i18n="subscriber_status">Subscriber service status</p></div></div>
            <div class="service-primary"><strong><?=$number($summary['active_services'] ?? 0)?></strong><span data-i18n="active_services">Active Services</span></div>
            <div class="service-line"><span class="dot green-dot"></span><span data-i18n="active">Active</span><strong><?=$number($summary['active_services'] ?? 0)?></strong></div>
            <div class="service-line"><span class="dot amber-dot"></span><span data-i18n="suspended">Suspended</span><strong><?=$number($summary['suspended_services'] ?? 0)?></strong></div>
            <div class="service-line"><span class="dot red-dot"></span><span data-i18n="overdue">Overdue</span><strong><?=$number($summary['overdue_invoices'] ?? 0)?></strong></div>
        </article>
    </section>

    <section class="section-title section-spaced"><div><span class="panel-kicker" data-i18n="quick_actions">QUICK ACTIONS</span><h2 data-i18n="daily_operations">Daily Operations</h2></div></section>
    <section class="quick-grid">
        <a href="/collection" class="quick-card primary"><span>৳</span><div><strong data-i18n="collection">Collection</strong><small data-i18n="collect_payment">Collect customer payment</small></div><b>→</b></a>
        <a href="/customers/create" class="quick-card"><span>＋</span><div><strong data-i18n="add_customer">Add Customer</strong><small data-i18n="create_subscriber">Create a new subscriber</small></div><b>→</b></a>
        <a href="/customers" class="quick-card"><span>⌕</span><div><strong data-i18n="search_customer">Search Customer</strong><small data-i18n="find_customer">Find by name, phone or code</small></div><b>→</b></a>
        <a href="/networking/customer" class="quick-card"><span>⌁</span><div><strong data-i18n="customer_networking">Customer Networking</strong><small data-i18n="customer_networking_subtitle">Live customer network information</small></div><b>→</b></a>
    </section>

    <section class="dashboard-columns lower-columns">
        <article class="panel">
            <div class="panel-head"><div><span class="panel-kicker" data-i18n="payments">PAYMENTS</span><h2 data-i18n="recent_collections">Recent Collections</h2><p class="muted" data-i18n="latest_payments">Latest completed payments</p></div><a href="/reports/collection" data-i18n="view_all">View all →</a></div>
            <div class="activity-list">
                <?php foreach ($payments as $payment): ?>
                    <div class="activity-row"><span class="activity-avatar">৳</span><div class="activity-copy"><strong><?=htmlspecialchars((string) ($payment['customer_name'] ?? 'Customer'), ENT_QUOTES, 'UTF-8')?></strong><small><?=htmlspecialchars((string) ($payment['customer_code'] ?? ''), ENT_QUOTES, 'UTF-8')?> · <?=htmlspecialchars((string) ($payment['method'] ?? ''), ENT_QUOTES, 'UTF-8')?> · <?=htmlspecialchars((string) ($payment['reference'] ?? ''), ENT_QUOTES, 'UTF-8')?></small></div><strong class="amount-positive"><?=$money((float) ($payment['amount'] ?? 0))?></strong></div>
                <?php endforeach; ?>
                <?php if ($payments === []): ?><div class="empty-state" data-i18n="no_payments">No completed collections yet.</div><?php endif; ?>
            </div>
        </article>
        <article class="panel">
            <div class="panel-head"><div><span class="panel-kicker" data-i18n="customers">CUSTOMERS</span><h2 data-i18n="recent_customers">Recent Customers</h2><p class="muted" data-i18n="newest_accounts">Newest subscriber accounts</p></div><a href="/customers" data-i18n="view_all">View all →</a></div>
            <div class="activity-list">
                <?php foreach ($customers as $customer): ?>
                    <a class="activity-row customer-row" href="/customers?id=<?= (int) ($customer['id'] ?? 0) ?>"><span class="activity-avatar customer-avatar">♙</span><div class="activity-copy"><strong><?=htmlspecialchars((string) ($customer['name'] ?? 'Customer'), ENT_QUOTES, 'UTF-8')?></strong><small><?=htmlspecialchars((string) ($customer['customer_code'] ?? ''), ENT_QUOTES, 'UTF-8')?> · <?=htmlspecialchars((string) ($customer['phone'] ?? 'No phone'), ENT_QUOTES, 'UTF-8')?></small></div><span class="status-badge <?=htmlspecialchars((string) ($customer['status'] ?? ''), ENT_QUOTES, 'UTF-8')?>"><?=htmlspecialchars(ucfirst((string) ($customer['status'] ?? 'unknown')), ENT_QUOTES, 'UTF-8')?></span></a>
                <?php endforeach; ?>
                <?php if ($customers === []): ?><div class="empty-state" data-i18n="no_customers">No customers found.</div><?php endif; ?>
            </div>
        </article>
    </section>

    <footer class="dashboard-footer"><span>ISPLUKA ISP ERP</span><span data-i18n="dashboard_footer">Billing · Customers · Network · Operations</span></footer>
</div>
</main>
</div>
<script src="/assets/js/app.js?v=7" defer></script>
</body>
</html>
