<?php

declare(strict_types=1);
namespace Ispluka\Core\Import;
use InvalidArgumentException;
final class ImportBatchPolicy
{
 public function validate(array $mapping):void{if(!in_array('name',$mapping,true)&&!in_array('pppoe_username',$mapping,true)&&!in_array('mobile',$mapping,true))throw new InvalidArgumentException('Import requires at least a customer identity field.');}
 public function duplicateKeys(array $row,array $mapping):array{$keys=[];foreach(['pppoe_username','mobile','nid','email'] as $field){$i=array_search($field,$mapping,true);if($i!==false&&isset($row[$i])&&trim((string)$row[$i])!=='')$keys[$field]=mb_strtolower(trim((string)$row[$i]));}return$keys;}
}
