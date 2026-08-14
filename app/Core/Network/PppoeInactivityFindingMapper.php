<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;

final class PppoeInactivityFindingMapper
{
    /** @return array<int,array{tenantId:int,routerId:int,username:string,type:string,severity:string,message:string,details:array}> */
    public function map(int $tenantId,int $days,array $rows):array
    {
        $out=[];
        foreach($rows as $row){
            $username=trim((string)($row['username']??''));
            if($username==='')continue;
            $out[]=[
                'tenantId'=>$tenantId,
                'routerId'=>(int)($row['router_id']??0),
                'username'=>$username,
                'type'=>PppoeReconciliationFinding::INACTIVE,
                'severity'=>'warning',
                'message'=>"PPPoE user has had no recent activity for {$days} days or more.",
                'details'=>[
                    'last_seen_at'=>$row['last_seen_at']??null,
                    'active'=>$row['active']??null,
                    'threshold_days'=>$days,
                ],
            ];
        }
        return $out;
    }
}
