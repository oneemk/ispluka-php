<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;

final class PppoeReconciliationEngine
{
    /** @param array<int,array<string,mixed>> $erp @param array<int,array<string,mixed>> $router */
    public function compare(int $tenantId,int $routerId,array $erp,array $router):array
    {
        $findings=[];$erpBy=[];$routerBy=[];
        foreach($erp as $r){$u=strtolower(trim((string)($r['username']??'')));if($u!=='')$erpBy[$u]=$r;}
        foreach($router as $r){$u=strtolower(trim((string)($r['name']??'')));if($u!=='')$routerBy[$u]=$r;}
        foreach($erpBy as $u=>$e){if(!isset($routerBy[$u]))$findings[]=$this->finding($tenantId,$routerId,$u,PppoeReconciliationFinding::ERP_MISSING_ON_ROUTER,'critical','ERP customer is missing on MikroTik.',[]);}
        foreach($routerBy as $u=>$r){if(!isset($erpBy[$u]))$findings[]=$this->finding($tenantId,$routerId,$u,PppoeReconciliationFinding::ROUTER_UNMAPPED,'warning','MikroTik PPPoE user has no ERP mapping.',['router'=>$r]);}
        foreach($erpBy as $u=>$e){if(!isset($routerBy[$u]))continue;$r=$routerBy[$u];$erpEnabled=(bool)($e['enabled']??false);$routerDisabled=(bool)($r['disabled']??false);if($erpEnabled===$routerDisabled)$findings[]=$this->finding($tenantId,$routerId,$u,PppoeReconciliationFinding::ENABLE_STATE_MISMATCH,'high','ERP and MikroTik enabled/disabled state does not match.',['erp_enabled'=>$erpEnabled,'router_disabled'=>$routerDisabled]);$ep=trim((string)($e['profile']??''));$rp=trim((string)($r['profile']??''));if($ep!==''&&$rp!==''&&$ep!==$rp)$findings[]=$this->finding($tenantId,$routerId,$u,PppoeReconciliationFinding::PROFILE_MISMATCH,'warning','ERP and MikroTik PPPoE profiles do not match.',['erp_profile'=>$ep,'router_profile'=>$rp]);}
        return $findings;
    }
    private function finding(int $tenantId,int $routerId,string $username,string $type,string $severity,string $message,array $details):array{return compact('tenantId','routerId','username','type','severity','message','details');}
}
