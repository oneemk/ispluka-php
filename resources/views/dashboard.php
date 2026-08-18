<?php
$summary = $snapshot['summary'] ?? [];
$payments = $snapshot['recentPayments'] ?? [];
$customers = $snapshot['recentCustomers'] ?? [];
$trend = $snapshot['collectionTrend'] ?? [];
$money = static fn(float|int $v): string => '৳' . number_format((float) $v, 0);
$number = static fn(float|int $v): string => number_format((float) $v);
$maxTrend = max(1.0, ...array_map(static fn(array $row): float => (float) ($row['amount'] ?? 0), $trend));
$csrfToken = htmlspecialchars((string) ($csrfToken ?? $csrf ?? ''), ENT_QUOTES, 'UTF-8');
$displayRole = ucwords(str_replace('_', ' ', (string) ($role ?? 'user')));
$greeting = in_array((string) ($role ?? ''), ['master_admin', 'admin'], true) ? 'Admin' : $displayRole;
?>
<!doctype html>
<html lang="en" data-lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="theme-color" content="#08111f">
    <link rel="stylesheet" href="/assets/css/app.css?v=4">
    <link rel="stylesheet" href="/assets/css/dashboard.css?v=5">
    <title>ISPLUKA Dashboard</title>
</head>
<body class="dashboard-page">
<div class="app-shell">
<header class="app-header dashboard-header">
    <div class="container header-inner">
        <button class="menu-toggle" type="button" data-menu-toggle aria-label="Open navigation" aria-expanded="false">☰</button>
        <a class="brand" href="/">ISPLUKA</a>
        <div class="header-tools">
            <label class="global-search">⌕<input type="search" data-search placeholder="Search customer, phone or code…" aria-label="Search customer"></label>
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
<nav class="nav dashboard-nav">
    <a class="active" href="/"><span data-i18n="dashboard">Dashboard</span></a>
    <div class="nav-section" data-i18n="operations">Operations</div>
    <a href="/collection"><span data-i18n="collection">Collection</span></a>
    <a href="/reports/collection"><span data-i18n="collection_report">Collection Report</span></a>
    <a href="/customers/create"><span data-i18n="add_customer">Add Customer</span></a>
    <a href="/customers"><span data-i18n="search_customer">Search Customer</span></a>
    <div class="nav-section" data-i18n="network">Network</div>
    <a href="/networking/mikrotik/routers"><span data-i18n="mikrotik_routers">MikroTik Routers</span></a>
    <a href="/networking/customer"><span data-i18n="customer_networking">Customer Networking</span></a>
    <a href="/networking/mikrotik/enforcement-audit"><span data-i18n="network_audit">Network Audit</span></a>
    <div class="nav-section" data-i18n="management">Management</div>
    <a href="/subscription"><span data-i18n="subscription">Subscription</span></a>
    <?php if (($role ?? '') === 'master_admin'): ?>
        <a href="/admin/tenants"><span data-i18n="tenants_admins">Tenants / Admins</span></a>
        <a href="/admin/subscriptions"><span data-i18n="platform_billing">Platform Billing</span></a>
    <?php endif; ?>
    <form method="post" action="/logout" class="sidebar-logout-form">
        <input type="hidden" name="_csrf" value="<?=$csrfToken?>">
        <button type="submit"><span data-i18n="logout">Logout</span></button>
    </form>
</nav>
</aside>

<main class="main main-with-sidebar dashboard-main">
<div class="container">
    <section class="welcome-row">
        <div>
            <span class="eyebrow" data-i18n="control_center">ISP CONTROL CENTER</span>
            <h1><span data-i18n="good_morning">Good Morning</span>, <?=htmlspecialchars($greeting, ENT_QUOTES, 'UTF-8')?> <span aria-hidden="true">👋</span></h1>
            <p><?=htmlspecialchars((string) $tenantName, ENT_QUOTES, 'UTF-8')?> · <span data-i18n="overview_subtitle">Your ISP operational overview at a glance.</span></p>
        </div>
        <div class="welcome-meta"><span class="scope-pill"><?=htmlspecialchars($displayRole, ENT_QUOTES, 'UTF-8')?></span></div>
    </section>

    <section class="shortcut-grid-focus" aria-label="Quick actions">
        <a class="shortcut shortcut-primary" href="/collection"><span><strong data-i18n="collection">Collection</strong><small data-i18n="collect_payment">Collect customer payment</small></span><b data-i18n="open">Open →</b></a>
        <a class="shortcut" href="/customers/create"><span><strong data-i18n="add_customer">Add Customer</strong><small data-i18n="create_subscriber">Create a new subscriber</small></span><b data-i18n="open">Open →</b></a>
        <a class="shortcut" href="/customers"><span><strong data-i18n="search_customer">Search Customer</strong><small data-i18n="find_customer">Find by name, phone or code</small></span><b data-i18n="open">Open →</b></a>
        <a class="shortcut" href="/networking/mikrotik/routers"><span><strong data-i18n="mikrotik_routers">MikroTik Routers</strong><small data-i18n="manage_network">Manage network infrastructure</small></span><b data-i18n="open">Open →</b></a>
    </section>

    <section class="section-heading"><div><span class="panel-kicker" data-i18n="overview">OVERVIEW</span><h2 data-i18n="business_snapshot">Business Snapshot</h2></div></section>
    <section class="stats-grid">
        <article class="metric-card"><div class="metric-icon blue">♙</div><div><span data-i18n="total_customers">Total Customers</span><strong><?= $number($summary['customers'] ?? 0) ?></strong><small>+<?= $number($summary['new_customers_today'] ?? 0) ?> <span data-i18n="today">today</span></small></div></article>
        <article class="metric-card"><div class="metric-icon green">✓</div><div><span data-i18n="active_services">Active Services</span><strong><?= $number($summary['active_services'] ?? 0) ?></strong><small data-i18n="currently_active">Currently active</small></div></article>
        <article class="metric-card"><div class="metric-icon amber">!</div><div><span data-i18n="suspended_services">Suspended Services</span><strong><?= $number($summary['suspended_services'] ?? 0) ?></strong><small data-i18n="service_status">Service status</small></div></article>
        <article class="metric-card"><div class="metric-icon red">!</div><div><span data-i18n="overdue_invoices_count">Overdue Invoices</span><strong><?= $number($summary['overdue_invoices'] ?? 0) ?></strong><small data-i18n="needs_attention">Needs attention</small></div></article>
        <article class="metric-card"><div class="metric-icon violet">৳</div><div><span data-i18n="outstanding">Due / Outstanding</span><strong><?= $money($summary['outstanding'] ?? 0) ?></strong><small data-i18n="receivable">Current receivable</small></div></article>
        <article class="metric-card"><div class="metric-icon cyan">৳</div><div><span data-i18n="today_collection">Today's Collection</span><strong><?= $money($summary['today_collected'] ?? 0) ?></strong><small data-i18n="completed_today">Completed today</small></div></article>
        <article class="metric-card"><div class="metric-icon indigo">৳</div><div><span data-i18n="monthly_collected">Monthly Collection</span><strong><?= $money($summary['monthly_collected'] ?? 0) ?></strong><small data-i18n="current_month">Current month</small></div></article>
        <article class="metric-card"><div class="metric-icon slate">◉</div><div><span data-i18n="routers">MikroTik Routers</span><strong><?= $number($summary['routers_total'] ?? 0) ?></strong><small><span class="inline-status online"><?= $number($summary['routers_online'] ?? 0) ?> <span data-i18n="online">online</span></span> · <span class="inline-status offline"><?= $number($summary['routers_offline'] ?? 0) ?> <span data-i18n="offline">offline</span></span></small></div></article>
    </section>

    <section class="section-heading section-heading-spaced"><div><span class="panel-kicker" data-i18n="network">NETWORK</span><h2 data-i18n="network_health">Network Health</h2></div><a href="/networking/mikrotik/routers" data-i18n="manage">Manage →</a></section>
    <section class="network-strip">
        <div class="network-status-card online-card"><span class="status-dot"></span><div><small data-i18n="online_routers">Online Routers</small><strong><?= $number($summary['routers_online'] ?? 0) ?></strong></div></div>
        <div class="network-status-card offline-card"><span class="status-dot"></span><div><small data-i18n="offline_routers">Offline Routers</small><strong><?= $number($summary['routers_offline'] ?? 0) ?></strong></div></div>
        <div class="network-status-card total-card"><span class="status-dot"></span><div><small data-i18n="total_routers">Total Routers</small><strong><?= $number($summary['routers_total'] ?? 0) ?></strong></div></div>
        <a class="network-cta" href="/networking/mikrotik/enforcement-audit"><span data-i18n="network_audit">Network Audit</span><b>→</b></a>
    </section>

    <section class="dashboard-grid-main">
        <article class="panel collection-panel">
            <div class="panel-head"><div><span class="panel-kicker" data-i18n="financial">FINANCIAL</span><h2 data-i18n="collection_overview">Collection Overview</h2><p class="muted" data-i18n="last_six_months">Completed payments · last 6 months</p></div><a href="/reports/collection" data-i18n="view_report">View report →</a></div>
            <div class="trend-chart">
                <?php foreach ($trend as $row): $height = max(8, min(100, ((float) ($row['amount'] ?? 0) / $maxTrend) * 100)); ?>
                    <div class="trend-item"><div class="trend-value"><?= $money((float) ($row['amount'] ?? 0)) ?></div><div class="trend-bar-wrap"><div class="trend-bar" style="height:<?= $height ?>%"></div></div><span><?= htmlspecialchars((string) ($row['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></div>
                <?php endforeach; ?>
                <?php if ($trend === []): ?><div class="empty-state" data-i18n="no_collection">Collection history will appear here after completed payments are recorded.</div><?php endif; ?>
            </div>
        </article>
        <article class="panel health-panel">
            <div class="panel-head"><div><span class="panel-kicker" data-i18n="services">SERVICES</span><h2 data-i18n="service_health">Service Health</h2><p class="muted" data-i18n="subscriber_status">Subscriber service status</p></div></div>
            <div class="service-summary"><div class="service-primary"><strong><?= $number($summary['active_services'] ?? 0) ?></strong><span data-i18n="active">Active Services</span></div><div class="service-line"><span class="dot green-dot"></span><span data-i18n="active">Active</span><strong><?= $number($summary['active_services'] ?? 0) ?></strong></div><div class="service-line"><span class="dot amber-dot"></span><span data-i18n="suspended">Suspended</span><strong><?= $number($summary['suspended_services'] ?? 0) ?></strong></div><div class="service-line"><span class="dot red-dot"></span><span data-i18n="overdue">Overdue</span><strong><?= $number($summary['overdue_invoices'] ?? 0) ?></strong></div></div>
        </article>
    </section>

    <section class="dashboard-grid-main lower-grid">
        <article class="panel">
            <div class="panel-head"><div><span class="panel-kicker" data-i18n="payments">PAYMENTS</span><h2 data-i18n="recent_collections">Recent Collections</h2><p class="muted" data-i18n="latest_payments">Latest completed payments</p></div><a href="/reports/collection" data-i18n="view_all">View all →</a></div>
            <div class="activity-list">
                <?php foreach ($payments as $payment): ?>
                    <div class="activity-row"><span class="activity-avatar">৳</span><div class="activity-copy"><strong><?= htmlspecialchars((string) $payment['customer_name'], ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars((string) $payment['method'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) $payment['reference'], ENT_QUOTES, 'UTF-8') ?></small></div><strong class="amount-positive"><?= $money((float) $payment['amount']) ?></strong></div>
                <?php endforeach; ?>
                <?php if ($payments === []): ?><div class="empty-state" data-i18n="no_payments">No completed collections yet.</div><?php endif; ?>
            </div>
        </article>
        <article class="panel">
            <div class="panel-head"><div><span class="panel-kicker" data-i18n="customers">CUSTOMERS</span><h2 data-i18n="recent_customers">Recent Customers</h2><p class="muted" data-i18n="newest_accounts">Newest subscriber accounts</p></div><a href="/customers" data-i18n="view_all">View all →</a></div>
            <div class="activity-list">
                <?php foreach ($customers as $customer): ?>
                    <a class="activity-row customer-row" href="/customers?id=<?= (int) $customer['id'] ?>"><span class="activity-avatar customer-avatar">♙</span><div class="activity-copy"><strong><?= htmlspecialchars((string) $customer['name'], ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars((string) $customer['customer_code'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) ($customer['phone'] ?? 'No phone'), ENT_QUOTES, 'UTF-8') ?></small></div><span class="status-badge <?= htmlspecialchars((string) $customer['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst((string) $customer['status']), ENT_QUOTES, 'UTF-8') ?></span></a>
                <?php endforeach; ?>
                <?php if ($customers === []): ?><div class="empty-state" data-i18n="no_customers">No customers found.</div><?php endif; ?>
            </div>
        </article>
    </section>
</div>
</main>
</div>
<script src="/assets/js/app.js?v=5" defer></script>
</body>
</html>
