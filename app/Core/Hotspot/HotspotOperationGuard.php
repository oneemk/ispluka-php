<?php

declare(strict_types=1);
namespace Ispluka\Core\Hotspot;
use RuntimeException;
final class HotspotOperationGuard {
 public function assertTenant(int $tenantId,int $recordTenantId):void { if($tenantId!==$recordTenantId) throw new RuntimeException('Hotspot resource not found.'); }
 public function assertActive(string $status):void { if(!in_array($status,['active','unused'],true)) throw new RuntimeException('Hotspot resource is not active.'); }
}
