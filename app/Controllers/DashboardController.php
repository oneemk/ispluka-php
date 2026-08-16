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
        return Response::text((string) ob_get_clean());
    }
}
