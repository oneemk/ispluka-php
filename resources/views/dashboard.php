<?php
$summary = is_array($snapshot['summary'] ?? null) ? $snapshot['summary'] : [];
$customers = is_array($snapshot['recentCustomers'] ?? null) ? $snapshot['recentCustomers'] : [];
$todayPayments = is_array($snapshot['todayPayments'] ?? null) ? $snapshot['todayPayments'] : [];
$money = static fn(float|int $v): string => '৳' . number_format((float)$v, 0);
$number = static fn(float|int $v): string => number_format((float)$v);
$csrfToken = htmlspecialchars((string)($csrfToken ?? $csrf ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!doctype html><html lang="en" data-lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><link rel="stylesheet" href="/assets/css/app.css?v=8"><link rel="stylesheet" href="/assets/css/dashboard.css?v=18"><link rel="stylesheet" href="/assets/css/dashboard-polish.css?v=7"><link rel="stylesheet" href="/assets/css/dashboard-contrast.css?v=4"><title>ISPLUKA — Dashboard</title></head><body class="dashboard-page">