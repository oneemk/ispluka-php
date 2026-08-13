<?php

declare(strict_types=1);
namespace Ispluka\Core\Import;
final readonly class MikroTikImportPreview
{
 public function __construct(public int $total,public int $ready,public int $incomplete,public int $invalid,public int $duplicates,public array $records){}
 public function summary():array{return['total'=>$this->total,'ready'=>$this->ready,'incomplete'=>$this->incomplete,'invalid'=>$this->invalid,'duplicates'=>$this->duplicates];}
}
