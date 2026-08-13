<?php

declare(strict_types=1);
namespace Ispluka\Core\Import;
final readonly class ImportValidationReport
{
 public function __construct(public int $total,public int $valid,public int $incomplete,public int $duplicates,public int $conflicts,public array $rows){}
 public function canImport():bool{return $this->valid>0;}
}
