<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;

use Closure;
use Throwable;

final class PppoeActivityRunCoordinator
{
    /** @param Closure(int,int):bool $due */
    public function __construct(private readonly PppoeActivityScheduler $scheduler, private readonly Closure $due) {}

    /** @param array<int,array{tenantId:int,routerId:int}> $routers */
    public function run(array $routers): array
    {
        $results=[];
        foreach($routers as $router){
            $tenantId=(int)($router['tenantId']??0);$routerId=(int)($router['routerId']??0);
            if($tenantId<1||$routerId<1)continue;
            if(!(($this->due)($tenantId,$routerId)))continue;
            try{$results[]=['tenantId'=>$tenantId,'routerId'=>$routerId]+$this->scheduler->run($tenantId,$routerId);}catch(Throwable $e){$results[]=['tenantId'=>$tenantId,'routerId'=>$routerId,'status'=>'failed','error'=>$e->getMessage()];}
        }
        return $results;
    }
}
