<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class PppoeActivityState
{
 public function __construct(public int $tenantId,public int $routerId,public string $username,public bool $online,public ?string $activeIp,public ?string $lastSeenAt,public ?int $uptimeSeconds,public ?int $rxBytes,public ?int $txBytes,public ?int $rxRateBps,public ?int $txRateBps,public bool $stale=false){}
 public function liveRates():array{return['rx_bps'=>$this->rxRateBps,'tx_bps'=>$this->txRateBps,'rx_mbps'=>$this->rxRateBps===null?null:round($this->rxRateBps/1000000,2),'tx_mbps'=>$this->txRateBps===null?null:round($this->txRateBps/1000000,2)];}
}
