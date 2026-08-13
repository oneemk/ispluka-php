<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class PppoeActivitySnapshot
{
 public function __construct(public int $tenantId,public int $routerId,public string $username,public bool $enabled,public bool $active,public ?string $activeIp,public ?string $callerId,public ?int $uptimeSeconds,public ?int $lastSeenAt,public ?int $rxBytes,public ?int $txBytes,public int $observedAt){}
 public function inactiveForDays(int $days,int $now):bool{return $this->lastSeenAt!==null&&$this->lastSeenAt<=$now-($days*86400);}
}
