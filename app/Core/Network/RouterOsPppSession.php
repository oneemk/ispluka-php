<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final readonly class RouterOsPppSession
{
 public function __construct(public string $username,public ?string $address,public ?string $callerId,public ?string $service,public ?string $profile,public ?int $uptimeSeconds,public ?int $rxBytes,public ?int $txBytes,public ?int $rxRateBps,public ?int $txRateBps){}
 public static function fromApiRow(array $row):self{return new self(trim((string)($row['name']??'')),self::str($row['address']??null),self::str($row['caller-id']??$row['caller_id']??null),self::str($row['service']??null),self::str($row['profile']??null),self::uptime($row['uptime']??null),self::int($row['rx-byte']??$row['rx_bytes']??null),self::int($row['tx-byte']??$row['tx_bytes']??null),self::int($row['rx-rate']??$row['rx_rate']??null),self::int($row['tx-rate']??$row['tx_rate']??null));}
 private static function str(mixed $v):?string{return $v===null||trim((string)$v)===''?null:trim((string)$v);}
 private static function int(mixed $v):?int{return is_numeric($v)?(int)$v:null;}
 private static function uptime(mixed $v):?int{if(is_numeric($v))return(int)$v;$s=(string)$v;if(!preg_match('/^(?:(\d+)w)?(?:(\d+)d)?(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$/',$s,$m))return null;return(int)($m[1]??0)*604800+(int)($m[2]??0)*86400+(int)($m[3]??0)*3600+(int)($m[4]??0)*60+(int)($m[5]??0);}
}
