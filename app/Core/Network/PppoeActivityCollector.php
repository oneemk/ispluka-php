<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use RuntimeException;
final class PppoeActivityCollector
{
    public function __construct(private readonly PppoeActivityRepository $repository, private readonly $command) {}
    public function collect(int $tenantId,int $routerId,int $limit=1000):int
    {
        $rows=($this->command)('/ppp/active/print',['=.proplist'=>'name,address,uptime,rx-byte,tx-byte','?disabled=no']);
        if(!is_array($rows)) throw new RuntimeException('Unable to collect MikroTik PPPoE sessions.');
        $count=0;$now=time();
        foreach(array_slice($rows,0,max(1,min(1000,$limit))) as $row){
            $username=trim((string)($row['name']??'')); if($username==='') continue;
            $this->repository->upsert(new PppoeActivityState($tenantId,$routerId,$username,true,$row['address']??null,$now,$this->duration($row['uptime']??null),$this->bytes($row['rx-byte']??null),$this->bytes($row['tx-byte']??null),null,null,false));
            $count++;
        }
        return $count;
    }
    private function bytes(mixed $v):?int{return is_numeric($v)?max(0,(int)$v):null;}
    private function duration(mixed $v):?int{if(!is_string($v)||$v==='')return null;if(!preg_match('/^(?:(\d+)w)?(?:(\d+)d)?(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$/',$v,$x))return null;return(int)($x[1]??0)*604800+(int)($x[2]??0)*86400+(int)($x[3]??0)*3600+(int)($x[4]??0)*60+(int)($x[5]??0);}
}
