<?php

declare(strict_types=1);

namespace Ispluka\Controllers;

use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Dashboard\DashboardService;
use Ispluka\Core\Http\Response;
use Ispluka\Core\Security\Csrf;
use RuntimeException;

final class DashboardController
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly AuthManager $auth,
        private readonly Csrf $csrf,
    ) {}

    public function page(): Response
    {
        $snapshot = $this->dashboard->snapshot($this->auth->tenantId());
        $role = $this->auth->roleCode() ?? 'user';
        $userId = (int) ($this->auth->userId() ?? 0);
        $tenantId = $this->auth->tenantId();
        $tenantName = $tenantId === null ? 'Platform' : 'Your ISP';

        $view = dirname(__DIR__, 2) . '/resources/views/dashboard.php';
        if (!is_file($view)) {
            throw new RuntimeException('Dashboard view is missing.');
        }

        extract([
            'snapshot' => $snapshot,
            'role' => $role,
            'userId' => $userId,
            'tenantName' => $tenantName,
            'csrfToken' => $this->csrf->token(),
        ], EXTR_SKIP);

        ob_start();
        require $view;
        $html = (string) ob_get_clean();

        // Keep the dashboard navigation in one place and organize every
        // currently routable ERP page by business category. API-only services
        // are intentionally not exposed as broken sidebar links.
        $sidebar = $this->sidebar($role, (string) ($csrfToken ?? ''));
        $html = (string) preg_replace(
            '/<aside\s+class="sidebar"\s+data-sidebar>.*?<\/aside>/s',
            $sidebar,
            $html,
            1,
        );

        return Response::text($html);
    }

    private function sidebar(string $role, string $csrfToken): string
    {
        $csrf = htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8');
        $master = $role === 'master_admin';

        $html = '<aside class="sidebar" data-sidebar>\n'
            . '<nav class="nav dashboard-nav">\n'
            . '<a class="active" href="/"><span>Dashboard</span></a>\n'
            . '<div class="nav-section">Customers</div>\n'
            . '<a href="/customers/create"><span>Add Customer</span></a>\n'
            . '<a href="/customers"><span>Customers</span></a>\n'
            . '<div class="nav-section">Billing &amp; Collection</div>\n'
            . '<a href="/collection"><span>Collection</span></a>\n'
            . '<a href="/reports/collection"><span>Collection Report</span></a>\n'
            . '<div class="nav-section">Network</div>\n'
            . '<a href="/networking/mikrotik/routers"><span>MikroTik Routers</span></a>\n'
            . '<a href="/networking/mikrotik/enforcement-audit"><span>Enforcement Audit</span></a>\n'
            . '<div class="nav-section">Services</div>\n'
            . '<a href="/networking/mikrotik/routers"><span>PPPoE / Active Sessions</span></a>\n'
            . '<div class="nav-section">Account</div>\n'
            . '<a href="/subscription"><span>Subscription</span></a>\n';

        if ($master) {
            $html .= '<div class="nav-section">Administration</div>\n'
                . '<a href="/admin/tenants"><span>Tenants / Admins</span></a>\n'
                . '<a href="/admin/subscriptions"><span>Platform Billing</span></a>\n';
        }

        $html .= '<form method="post" action="/logout" class="sidebar-logout-form">\n'
            . '<input type="hidden" name="_csrf" value="' . $csrf . '">\n'
            . '<button type="submit"><span>Logout</span></button>\n'
            . '</form>\n'
            . '</nav>\n'
            . '</aside>';

        return $html;
    }
}
