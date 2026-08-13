<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use RuntimeException;
final class RouterOsPppActiveCollector
{
 /** @param callable(string):array $query */
 public function __construct(private readonly $query){}
 public function collect(int $routerId):RouterOsPppActiveSnapshot
 {
  $rows=($this->query)('/ppp/active/print');
  if(!is_array($rows))throw new RuntimeException('RouterOS PPP active response is invalid.');
  $sessions=[];foreach($rows as $row){if(is_array($row))$sessions[]=RouterOsPppSession::fromApiRow($row);}
  return new RouterOsPppActiveSnapshot($routerId,$sessions,gmdate('c'));
 }
}
