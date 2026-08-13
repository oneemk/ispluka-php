<?php

declare(strict_types=1);

namespace Ispluka\Core\Billing;

use Ispluka\Core\Database\Database;

final class BillingJob
{
    public function __construct(private readonly Database $database) {}

    public function run(int $batchSize = 100): int
    {
        $pdo = $this->database->pdo();
        $batchSize = min(max($batchSize, 1), 500);
        $stmt = $pdo->prepare("SELECT id FROM customer_services WHERE status = 'active' AND auto_suspend = TRUE AND next_billing_at <= CURRENT_TIMESTAMP ORDER BY next_billing_at ASC LIMIT :limit");
        $stmt->bindValue(':limit', $batchSize, \PDO::PARAM_INT);
        $stmt->execute();
        $count = 0;
        foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $serviceId) {
            $update = $pdo->prepare("UPDATE customer_services SET next_billing_at = CURRENT_TIMESTAMP + INTERVAL '1 month', updated_at = CURRENT_TIMESTAMP WHERE id = :id AND status = 'active'");
            $update->execute([':id' => $serviceId]);
            $count += $update->rowCount();
        }
        return $count;
    }
}
