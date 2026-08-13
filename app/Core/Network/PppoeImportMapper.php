<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final class PppoeImportMapper
{
 /** @param RouterOsPppSession[] $sessions */
 public function map(int $routerId,array $sessions,array $existingUsernames=[]):array{$existing=array_fill_keys(array_map('strval',$existingUsernames),true);$out=[];foreach($sessions as $s){if($s->username==='')continue;$out[]=new ImportedPppoeUser($routerId,$s->username,$s->profile,$s->address,$s->callerId,true,isset($existing[$s->username]));}return$out;}
}
