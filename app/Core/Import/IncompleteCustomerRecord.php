<?php

declare(strict_types=1);
namespace Ispluka\Core\Import;
final readonly class IncompleteCustomerRecord
{
 public function __construct(public array $data,public array $missingFields,public string $source='mikrotik'){}
 public function isComplete():bool{return $this->missingFields===[];}
}
