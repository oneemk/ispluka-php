<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class PppoeImportResult
{
 public function __construct(public int $seen,public int $created,public int $updated,public int $skipped,public array $errors=[]){ }
 public function hasErrors():bool{return $this->errors!==[];}
}
