<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class RouterCollectionResult
{
 public function __construct(public int $routerId,public bool $success,public int $usersSeen,public int $usersChanged,public ?string $error=null,public ?string $syncedAt=null){}
}
