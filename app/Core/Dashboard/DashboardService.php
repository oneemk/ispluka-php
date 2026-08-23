<?php

declare(strict_types=1);

namespace Ispluka\Core\Dashboard;

use Ispluka\Core\Database\Database;
use PDO;

final class DashboardService
{
    public function __construct(private readonly Database $db) {}

    public function snapshot(?int $tenantId): array
    {
        $pdo = $this->db->pdo();
        $scope = $tenantId === null ? '1=1' : 'tenant_id=:tenant_id';
        $params = $tenantId === null ? [] : ['tenant_id' => $tenantId];
        $scalar = static function (PDO $pdo, string $sql, array $params = []): mixed {
            $s = $pdo->prepare($sql);
            $s->execute($params);
            return $s->fetchColumn();
        };
        $rows = static function (PDO $pdo, string $sql, array $params = []): array {
            $s = $pdo->prepare($sql);
            $s->execute($params);
            return $s->fetchAll(PDO::FETCH_ASSOC);
        };

        $summary = [
            'customers' => (int)$scalar($pdo, "SELECT COUNT(*) FROM customers WHERE {$scope} AND deleted_at IS NULL", $params),
            'active_services' => (int)$scalar($pdo, "SELECT COUNT(*) FROM customer_services WHERE {$scope} AND status='active'", $params),
            'suspended_services' => (int)$scalar($pdo, "SELECT COUNT(*) FROM customer_services WHERE {$scope} AND status='suspended'", $params),
            'overdue_invoices' => (int)$scalar($pdo, "SELECT COUNT(*) FROM invoices WHERE {$scope} AND status='overdue'", $params),
            'outstanding' => (float)$scalar($pdo, "SELECT COALESCE(SUM(total-paid_amount),0) FROM invoices WHERE {$scope} AND status IN ('unpaid','partial','overdue')", $params),
            'monthly_collected' => (float)$scalar($pdo, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE {$scope} AND status='completed' AND paid_at>=date_trunc('month',CURRENT_TIMESTAMP)", $params),
            'today_collected' => (float)$scalar($pdo, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE {$scope} AND status='completed' AND paid_at>=CURRENT_DATE", $params),
            'new_customers_today' => (int)$scalar($pdo, "SELECT COUNT(*) FROM customers WHERE {$scope} AND deleted_at IS NULL AND created_at>=CURRENT_DATE", $params),
            'routers_total' => (int)$scalar($pdo, "SELECT COUNT(*) FROM routers WHERE {$scope}", $params),
            'routers_online' => (int)$scalar($pdo, "SELECT COUNT(*) FROM routers WHERE {$scope} AND status='online'", $params),
            'routers_offline' => (int)$scalar($pdo, "SELECT COUNT(*) FROM routers WHERE {$scope} AND status='offline'", $params),
        ];

        $joinScope = $tenantId === null ? '' : ' AND p.tenant_id=:tenant_id';
        $customerScope = $tenantId === null ? '' : ' AND c.tenant_id=:tenant_id';
        $recentCustomers = $rows($pdo, "SELECT c.id,c.customer_code,c.name,c.phone,c.status,c.created_at FROM customers c WHERE c.deleted_at IS NULL{$customerScope} AND c.created_at>=date_trunc('month',CURRENT_DATE) ORDER BY c.created_at DESC,c.id DESC LIMIT 6", $params);
        $todayPayments = $rows($pdo, "SELECT p.reference,p.amount,p.method,p.paid_at,c.name AS customer_name,c.customer_code FROM payments p JOIN customers c ON c.id=p.customer_id WHERE p.status='completed' AND p.paid_at>=CURRENT_DATE{$joinScope} ORDER BY p.paid_at DESC NULLS LAST,p.id DESC LIMIT 8", $params);

        return [
            'summary' => $summary,
            'recentCustomers' => $recentCustomers,
            'todayPayments' => $todayPayments,
        ];
    }
}
