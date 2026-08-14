<?php

declare(strict_types=1);
namespace Ispluka\Controllers;
use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
use Ispluka\Core\Network\MikrotikAutomationService;
use Ispluka\Core\Network\PppoeEnforcementAuditQuery;
use PDO;
use Throwable;
final class MikrotikManualActionController
{
 public function __construct(private readonly PDO $pdo,private readonly AuthManager $auth,private readonly MikrotikAutomationService $automation,private readonly PppoeEnforcementAuditQuery $audit){}
 public function execute(Request $r):Response{$tenant=(int)($this->auth->tenantId()??0);$service=(int)$r->input('service_id',0);$action=strtolower(trim((string)$r->input('action','')));if($tenant<1||$service<1||!in_array($action,['enable','disable','suspend'],true))return Response::json(['error'=>['message'=>'Tenant, service_id and a valid action are required.']],422);$requested=['service_id'=>$service,'action'=>$action,'source'=>'manual'];try{$result=$this->automation->execute($tenant,$service,$action);$status=$this->verify($tenant,$service,$action,$result);$this->log($tenant,$service,$action,$status,$requested,$result,null);return Response::json(['data'=>['status'=>$status,'action'=>$action,'result'=>$result]]);}catch(Throwable $e){$this->log($tenant,$service,$action,'failed',$requested,null,$e->getMessage());return Response::json(['error'=>['message'=>'MikroTik action failed.'],'data'=>['status'=>'failed']],502);}}
 private function verify(int $tenant,int $service,string $action,array $result):string{$requested=$result['requested']??[];$ok=true;if($action==='suspend')$ok=(($requested['profile']??'')==='suspend');elseif($action==='disable')$ok=(($requested['disabled']??'')==='yes');elseif($action==='enable')$ok=(($requested['disabled']??'')==='no');return $ok?'success':'mismatch';}
 private function log(int $tenant,int $service,string $action,string $status,array $requested,?array $result,?string $error):void{$q=$this->pdo->prepare("INSERT INTO pppoe_enforcement_log (tenant_id,service_id,action,source,status,requested_state,actual_state,error_message,created_at) VALUES (:t,:s,:a,'manual',:st,:req,:act,:err,NOW())");$q->execute([':t'=>$tenant,':s'=>$service,':a'=>$action,':st'=>$status,':req'=>json_encode($requested,JSON_UNESCAPED_SLASHES),':act'=>json_encode($result['result']??null,JSON_UNESCAPED_SLASHES),':err'=>$error]);}
}
