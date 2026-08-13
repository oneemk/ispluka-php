<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class RouterReconciliation
{
 public function compare(array $erp,array $router):array{$all=array_unique(array_merge(array_keys($erp),array_keys($router)));$out=[];foreach($all as $u){$e=$erp[$u]??null;$r=$router[$u]??null;$out[]=['username'=>$u,'missing_in_erp'=>$e===null,'missing_in_router'=>$r===null,'enabled_mismatch'=>$e!==null&&$r!==null&&$e['enabled']!==$r['enabled'],'profile_mismatch'=>$e!==null&&$r!==null&&$e['profile']!==$r['profile']];}return$out;}
}
