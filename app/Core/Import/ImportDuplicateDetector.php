<?php

declare(strict_types=1);
namespace Ispluka\Core\Import;
final class ImportDuplicateDetector
{
 public function find(array $rows):array{$seen=[];$duplicates=[];foreach($rows as $index=>$row){foreach(['pppoe_username','mobile','nid','email'] as $field){$v=trim((string)($row[$field]??''));if($v==='')continue;$key=$field.':'.mb_strtolower($v);if(isset($seen[$key]))$duplicates[$index][]=['field'=>$field,'value'=>$v,'first_row'=>$seen[$key]];else$seen[$key]=$index;}}return$duplicates;}
}
