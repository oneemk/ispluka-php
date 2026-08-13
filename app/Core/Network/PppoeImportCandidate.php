<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class PppoeImportCandidate
{
 public function __construct(public int $tenantId,public int $routerId,public string $username,public ?string $profile,public ?string $activeIp,public ?string $callerId,public ?int $mappedCustomerId=null,public string $status='pending'){}
 public function complete(int $customerId):self{return new self($this->tenantId,$this->routerId,$this->username,$this->profile,$this->activeIp,$this->callerId,$customerId,'completed');}
}
