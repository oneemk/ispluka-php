<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class RouterUnknownUserPolicy
{
 public function classify(array $routerUsers,array $erpUsers):array{$erp=array_fill_keys($erpUsers,true);$out=[];foreach($routerUsers as $username){$u=trim((string)$username);if($u!==''&&!isset($erp[$u]))$out[]=$u;}return array_values(array_unique($out));}
}
