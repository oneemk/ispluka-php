<?php

declare(strict_types=1);
namespace Ispluka\Core\Api;
final readonly class ApiToken { public function __construct(public int $userId, public int $tenantId, public array $scopes=[]) {} public function allows(string $scope): bool { return in_array('*',$this->scopes,true)||in_array($scope,$this->scopes,true); } }
