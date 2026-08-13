<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use InvalidArgumentException;
final readonly class PppoeBulkImportGuard
{
 public function __construct(public int $maxBatch=1000){}
 public function validate(array $users):void{if(count($users)>$this->maxBatch)throw new InvalidArgumentException('Import batch is too large; split it into smaller batches.');}
}
