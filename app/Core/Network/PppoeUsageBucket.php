<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use DateTimeImmutable;
final readonly class PppoeUsageBucket
{
 public static function hour(DateTimeImmutable $at):string{return $at->setTime((int)$at->format('H'),0,0)->format('Y-m-d H:00:00P');}
}
