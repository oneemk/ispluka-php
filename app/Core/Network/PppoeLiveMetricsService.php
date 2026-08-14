<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;

use Closure;

final class PppoeLiveMetricsService
{
    /** @param Closure(int,int,string):?array $reader */
    public function __construct(private readonly Closure $reader) {}
    public function get(int $tenantId,int $routerId,string $username):?array
    {
        $username=trim($username); if($tenantId<1||$routerId<1||$username==='')return null;
        $row=($this->reader)($tenantId,$routerId,$username); if(!is_array($row))return null;
        return ['username'=>$username,'active_ip'=>$row['address']??null,'online'=>(bool)($row['online']??true),'rx_bytes'=>isset($row['rx-byte'])?(int)$row['rx-byte']:null,'tx_bytes'=>isset($row['tx-byte'])?(int)$row['tx-byte']:null,'rx_rate_bps'=>isset($row['rx-rate'])?(int)$row['rx-rate']:null,'tx_rate_bps'=>isset($row['tx-rate'])?(int)$row['tx-rate']:null,'uptime'=>$row['uptime']??null];
    }
}
