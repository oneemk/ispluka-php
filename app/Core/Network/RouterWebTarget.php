<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use InvalidArgumentException;
final readonly class RouterWebTarget
{
 public function __construct(public string $host,public int $port=8080){}
 public static function fromConfiguredHost(string $host,int $port=8080):self{if(!filter_var($host,FILTER_VALIDATE_IP)&&!preg_match('/^[a-zA-Z0-9.-]+$/',$host))throw new InvalidArgumentException('Invalid router management host.');if($port<1||$port>65535)throw new InvalidArgumentException('Invalid router management port.');return new self($host,$port);}
 public function url():string{return 'http://'.$this->host.':'.$this->port.'/';}
}
