<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class RouterReconciliationReport
{
 public function summarize(array $rows):array{$s=['total'=>count($rows),'missing_in_erp'=>0,'missing_in_router'=>0,'enabled_mismatch'=>0,'profile_mismatch'=>0];foreach($rows as $r)foreach(array_keys($s) as $k)if($k!=='total'&&($r[$k]??false))$s[$k]++;return$s;}
}
