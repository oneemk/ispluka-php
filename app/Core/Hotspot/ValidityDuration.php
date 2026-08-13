<?php

declare(strict_types=1);
namespace Ispluka\Core\Hotspot;
use InvalidArgumentException;
final readonly class ValidityDuration {
 public function __construct(public int $seconds, public string $normalized) { if($seconds<=0) throw new InvalidArgumentException('Validity duration must be greater than zero.'); }
 public static function parse(string $input):self { $value=trim(strtolower($input)); if($value==='') throw new InvalidArgumentException('Validity duration is required.'); preg_match_all('/(\d+)\s*(d|h|m|s)/',$value,$m,PREG_SET_ORDER); if(!$m||implode('',array_map(fn($x)=>$x[0],$m))!==preg_replace('/\s+/','',$value)) throw new InvalidArgumentException('Invalid validity duration. Use values such as 15d, 20h, 90m or 2d 12h.'); $seconds=0;$parts=[];foreach($m as $part){$n=(int)$part[1];$u=$part[2];if($n<=0)throw new InvalidArgumentException('Validity duration values must be positive.');$seconds+=$n*match($u){'d'=>86400,'h'=>3600,'m'=>60,'s'=>1};$parts[]=$n.$u;}return new self($seconds,implode(' ',$parts)); }
}
