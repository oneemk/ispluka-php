<?php

declare(strict_types=1);
namespace Ispluka\Core\Import;
final class ImportRowClassifier
{
 public function classify(array $row,array $mapping):array
 {
  $identity=[];$missing=[];
  foreach(['name','mobile','pppoe_username'] as $field){$i=array_search($field,$mapping,true);$v=$i===false?'':trim((string)($row[$i]??''));if($v!=='')$identity[$field]=$v;}
  if($identity===[])return ['status'=>'invalid','reason'=>'No customer identity detected.'];
  foreach(['name','mobile','pppoe_username'] as $field){if(!isset($identity[$field]))$missing[]=$field;}
  return ['status'=>$missing===[]?'ready':'incomplete','missing'=>$missing,'identity'=>$identity];
 }
}
