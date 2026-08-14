<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;

use PDO;

final class PppoeUsageReport
{
    public function __construct(private readonly PDO $pdo) {}

    /** @return array<int,array<string,mixed>> */
    public function monthly(int $tenantId,int $routerId,string $username,string $from,string $to):array
    {
        $sql="SELECT DATE_TRUNC('month',bucket_start) month_start,SUM(rx_bytes) rx_bytes,SUM(tx_bytes) tx_bytes,SUM(online_seconds) online_seconds,SUM(samples) samples
              FROM pppoe_usage_hourly
              WHERE tenant_id=:t AND router_id=:r AND username=:u AND bucket_start>=:from AND bucket_start<:to
              GROUP BY DATE_TRUNC('month',bucket_start) ORDER BY month_start";
        $q=$this->pdo->prepare($sql);$q->execute([':t'=>$tenantId,':r'=>$routerId,':u'=>$username,':from'=>$from,':to'=>$to]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
}
