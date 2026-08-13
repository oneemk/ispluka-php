<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class ImportedPppoeUser
{
 public function __construct(public int $routerId,public string $username,public ?string $profile,public ?string $activeIp,public ?string $callerId,public bool $enabled=false,public bool $mapped=false){}
 public function incomplete():bool{return trim($this->username)===''||$this->profile===null||$this->mapped===false;}
}
