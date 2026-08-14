<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;

use PDO;

final class PppoeReconciliationStore
{
    public function __construct(private readonly PDO $pdo) {}

    /** @param array<int,array<string,mixed>> $findings */
    public function sync(int $tenantId,int $routerId,array $findings,int $now):array
    {
        $seen=[];$opened=0;$resolved=0;
        foreach($findings as $f){
            $username=trim((string)($f['username']??''));$type=trim((string)($f['type']??''));
            if($username===''||$type==='')continue;
            $seen[$type.'|'.$username]=true;
            $sql="INSERT INTO pppoe_reconciliation_findings(tenant_id,router_id,username,finding_type,severity,message,details,status,first_seen_at,last_seen_at) VALUES(:t,:r,:u,:ft,:s,:m,:d,'open',TO_TIMESTAMP(:n),TO_TIMESTAMP(:n)) ON CONFLICT DO NOTHING";
            $this->pdo->prepare($sql)->execute([':t'=>$tenantId,':r'=>$routerId,':u'=>$username,':ft'=>$type,':s'=>$f['severity']??'warning',':m'=>$f['message']??$type,':d'=>json_encode($f['details']??[],JSON_THROW_ON_ERROR),':n'=>$now]);
            $sql="UPDATE pppoe_reconciliation_findings SET status='open',severity=:s,message=:m,details=:d,last_seen_at=TO_TIMESTAMP(:n),resolved_at=NULL WHERE tenant_id=:t AND router_id=:r AND username=:u AND finding_type=:ft";
            $this->pdo->prepare($sql)->execute([':s'=>$f['severity']??'warning',':m'=>$f['message']??$type,':d'=>json_encode($f['details']??[],JSON_THROW_ON_ERROR),':n'=>$now,':t'=>$tenantId,':r'=>$routerId,':u'=>$username,':ft'=>$type]);
            $opened++;
        }
        $rows=$this->pdo->prepare("SELECT id,finding_type,username FROM pppoe_reconciliation_findings WHERE tenant_id=:t AND router_id=:r AND status='open'");$rows->execute([':t'=>$tenantId,':r'=>$routerId]);
        foreach($rows->fetchAll(PDO::FETCH_ASSOC) as $row){$key=$row['finding_type'].'|'.$row['username'];if(isset($seen[$key]))continue;$q=$this->pdo->prepare("UPDATE pppoe_reconciliation_findings SET status='resolved',resolved_at=TO_TIMESTAMP(:n),last_seen_at=TO_TIMESTAMP(:n) WHERE id=:id AND status='open'");$q->execute([':n'=>$now,':id'=>$row['id']]);$resolved+=(int)$q->rowCount();}
        return ['opened_or_refreshed'=>$opened,'resolved'=>$resolved];
    }
}
