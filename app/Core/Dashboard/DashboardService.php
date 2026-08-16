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
        $where = $tenantId === null ? '' : ' WHERE tenant_id = :tenant_id';
        $params = $tenantId === null ? [] : ['tenant_id' => $tenantId];

        $scalar = static function (PDO $pdo, string $sql, array $params = []): mixed {
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            return $statement->fetchColumn();
        };

        $rows = static function (PDO $pdo, string $sql, array $params = []): array {
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        };

        $summary = [
            'customers' => (int) $scalar($pdo, "SELECT COUNT(*) FROM customers{$where}", $params),
            'active_services' => (int) $scalar($pdo, "SELECT COUNT(*) FROM customer_services{$where} AND status='active'", $params),
            'suspended_services' => (int) $scalar($pdo, "SELECT COUNT(*) FROM customer_services{$where} AND status='suspended'", $params),
            'overdue_invoices' => (int) $scalar($pdo, "SELECT COUNT(*) FROM invoices{$where} AND status='overdue'", $params),
            'outstanding' => (float) $scalar($pdo, "SELECT COALESCE(SUM(total-paid_amount),0) FROM invoices{$where} AND status IN ('issued','partial','overdue')", $params),
            'monthly_collected' => (float) $scalar($pdo, "SELECT COALESCE(SUM(amount),0) FROM payments{$where} AND status='completed' AND paid_at>=date_trunc('month',CURRENT_TIMESTAMP)", $params),
            'today_collected' => (float) $scalar($pdo, "SELECT COALESCE(SUM(amount),0) FROM payments{$where} AND status='completed' AND paid_at>=CURRENT_DATE", $params),
            'new_customers_today' => (int) $scalar($pdo, "SELECT COUNT(*) FROM customers{$where} AND created_at>=CURRENT_DATE", $params),
        ];

        $recentParams = $tenantId === null ? [] : ['tenant_id' => $tenantId];
        $recentWhere = $tenantId === null ? '' : ' AND c.tenant_id=:tenant_id';
        $paymentWhere = $tenantId === null ? '' : ' WHERE p.tenant_id=:tenant_id';

        $recentPayments = $rows($pdo, "SELECT p.reference,p.amount,p.method,p.paid_at,c.name AS customer_name,c.customer_code FROM payments p JOIN customers c ON c.id=p.customer_id{$paymentWhere} AND p.status='completed' ORDER BY p.paid_at DESC NULLS LAST,p.id DESC LIMIT 6", $recentParams);
        $recentCustomers = $rows($pdo, "SELECT c.id,c.customer_code,c.name,c.phone,c.status,c.created_at FROM customers c WHERE c.deleted_at IS NULL{$recentWhere} ORDER BY c.created_at DESC,c.id DESC LIMIT 6", $recentParams);
        $collectionTrend = $rows($pdo, "SELECT to_char(date_trunc('month',paid_at),'Mon') AS label,COALESCE(SUM(amount),0) AS amount FROM payments p WHERE p.status='completed' AND paid_at>=date_trunc('month',CURRENT_DATE)-INTERVAL '5 months'" . ($tenantId === null ? '' : ' AND p.tenant_id=:tenant_id') . " GROUP BY date_trunc('month',paid_at) ORDER BY date_trunc('month',paid_at)", $recentParams);

        return compact('summary','recentPayments','recentCustomers','collectionTrend');
    }
}
