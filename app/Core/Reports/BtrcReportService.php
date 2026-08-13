<?php

declare(strict_types=1);
namespace Ispluka\Core\Reports;
use Ispluka\Core\Database\Database;
final class BtrcReportService {
 public function __construct(private readonly Database $db) {}
 public function generate(int $tenantId,string $from,string $to,?int $userId=null):array{$pdo=$this->db->pdo();$q=function(string $sql)use($pdo,$tenantId,$from,$to){$s=$pdo->prepare($sql);$s->execute([':t'=>$tenantId,':f'=>$from,':to'=>$to]);return$s->fetch();};$payload=['period'=>['from'=>$from,'to'=>$to],'customers'=>(int)$q('SELECT COUNT(*) FROM customers WHERE tenant_id=:t AND created_at>=:f::date AND created_at<(:to::date+INTERVAL \'1 day\')')['count'],'active_services'=>(int)$q("SELECT COUNT(*) FROM customer_services WHERE tenant_id=:t AND status='active'")['count'],'payments'=>(int)$q("SELECT COALESCE(SUM(amount),0) count FROM payments WHERE tenant_id=:t AND status='completed' AND paid_at>=:f::date AND paid_at<(:to::date+INTERVAL '1 day')")['count']];$s=$pdo->prepare('INSERT INTO btrc_reports(tenant_id,period_start,period_end,status,payload,generated_by) VALUES(:t,:f::date,:to::date,\'generated\',:p::jsonb,:u) ON CONFLICT(tenant_id,period_start,period_end) DO UPDATE SET status=\'generated\',payload=EXCLUDED.payload,generated_by=EXCLUDED.generated_by');$s->execute([':t'=>$tenantId,':f'=>$from,':to'=>$to,':p'=>json_encode($payload,JSON_THROW_ON_ERROR),':u'=>$userId]);return$payload;}
}
