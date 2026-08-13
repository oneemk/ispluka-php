<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use RuntimeException;
final class RouterOsPppEnforcer
{
 /** @param callable(string,array):mixed $command */
 public function __construct(private readonly $command){}
 public function enable(string $username):void{$this->run('/ppp/secret/enable',['.id'=>$username]);}
 public function disable(string $username):void{$this->run('/ppp/secret/disable',['.id'=>$username]);}
 public function setProfile(string $username,string $profile):void{$this->run('/ppp/secret/set',['.id'=>$username,'profile'=>$profile]);}
 private function run(string $path,array $args):void{try{$result=($this->command)($path,$args);if($result===false)throw new RuntimeException('RouterOS command failed.');}catch(\Throwable $e){throw new RuntimeException('MikroTik PPPoE enforcement failed: '.$e->getMessage(),0,$e);}}
}
