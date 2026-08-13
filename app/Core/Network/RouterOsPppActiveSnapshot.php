<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class RouterOsPppActiveSnapshot
{
 /** @param RouterOsPppSession[] $sessions */
 public function __construct(public int $routerId,public array $sessions,public string $collectedAt){}
 public function byUsername():array{$out=[];foreach($this->sessions as $session)if($session->username!=='')$out[$session->username]=$session;return$out;}
}
