<?php

declare(strict_types=1);
namespace Ispluka\Controllers;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
use Ispluka\Core\Network\PppoeActivityRepository;
use Ispluka\Core\Network\PppoeEnforcementAuditQuery;
use PDO;
final class MikrotikEnforcementAuditController
{
 public function __construct(private readonly PDO $pdo){}
 private function tenant(Request $r):int{return (int)($r->attribute('tenant_id')??0);}
 public function audit(Request $r):Response{$t=$this->tenant($r);$status=trim((string)($r->input('status')??''));$limit=(int)($r->input('limit')??50);$offset=(int)($r->input('offset')??0);return Response::json(['data'=>(new PppoeEnforcementAuditQuery($this->pdo))->list($t,$status!==''?$status:null,$limit,$offset)]);}
 public function summary(Request $r):Response{return Response::json(['data'=>(new PppoeEnforcementAuditQuery($this->pdo))->summary($this->tenant($r))]);}
 public function live(Request $r):Response
 {
  $tenant=$this->tenant($r);$router=(int)($r->input('router_id')??0);$username=trim((string)($r->input('username')??''));
  if($tenant<1||$router<1||$username==='')return Response::json(['error'=>['message'=>'Tenant, router_id and username are required.']],422);
  $row=(new PppoeActivityRepository($this->pdo))->find($tenant,$router,$username);
  if($row===null)return Response::json(['error'=>['message'=>'No activity snapshot found.']],404);
  $row['source']='activity_snapshot';$row['note']='Metrics are from the latest bounded MikroTik collection; stale indicates collection age.';
  return Response::json(['data'=>$row]);
 }
 public function reconciliation(Request $r):Response
 {
  $tenant=$this->tenant($r); if($tenant<1)return Response::json(['error'=>['message'=>'Tenant context required.']],403);
  $status=trim((string)($r->input('status')??''));$severity=trim((string)($r->input('severity')??''));$router=(int)($r->input('router_id')??0);
  $where=['tenant_id=:tenant'];$params=[':tenant'=>$tenant];
  if(in_array($status,['open','resolved'],true)){$where[]='status=:status';$params[':status']=$status;}
  if(in_array($severity,['critical','high','warning','info'],true)){$where[]='severity=:severity';$params[':severity']=$severity;}
  if($router>0){$where[]='router_id=:router';$params[':router']=$router;}
  $sql='SELECT id,router_id,username,finding_type,severity,message,details,status,first_seen_at,last_seen_at,resolved_at FROM pppoe_reconciliation_findings WHERE '.implode(' AND ',$where).' ORDER BY CASE severity WHEN \'critical\' THEN 1 WHEN \'high\' THEN 2 WHEN \'warning\' THEN 3 ELSE 4 END,last_seen_at DESC LIMIT 500';
  $q=$this->pdo->prepare($sql);$q->execute($params);$rows=$q->fetchAll(PDO::FETCH_ASSOC);
  $summary=['critical'=>0,'high'=>0,'warning'=>0,'info'=>0,'open'=>0,'resolved'=>0];foreach($rows as $row){$s=(string)$row['severity'];$st=(string)$row['status'];if(isset($summary[$s]))$summary[$s]++;if(isset($summary[$st]))$summary[$st]++;}
  return Response::json(['data'=>$rows,'summary'=>$summary]);
 }
 public function page(Request $r):Response{return Response::text('<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#0f172a"><link rel="stylesheet" href="/assets/css/app.css"><title>PPPoE Enforcement Audit</title></head><body><main class="audit-wrap"><h1>PPPoE Enforcement Audit</h1></main></body></html>');}
}
