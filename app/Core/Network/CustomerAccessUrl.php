<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use InvalidArgumentException;
final class CustomerAccessUrl
{
 public static function routerWeb(string $ip,int $port=8080,string $scheme='http'):string
 {
  $scheme=strtolower($scheme); if(!in_array($scheme,['http','https'],true)) throw new InvalidArgumentException('Unsupported management protocol.');
  $ip=trim($ip); if(filter_var($ip,FILTER_VALIDATE_IP)===false) throw new InvalidArgumentException('Invalid active IP address.');
  if($port<1||$port>65535) throw new InvalidArgumentException('Invalid management port.');
  return $scheme.'://'.(str_contains($ip,':')?'['.$ip.']':$ip).':'.$port.'/';
 }
}
