<?php

declare(strict_types=1);
namespace Ispluka\Core\Billing;
use Ispluka\Core\Database\Database;
use RuntimeException;
final class InvoiceService {
 public function __construct(private readonly Database $db) {}
 public function createForService(int $tenantId,int $serviceId,string $dueDate):int {
  $pdo=$this->db->pdo(); $pdo->beginTransaction();
  try {
   $s=$pdo->prepare("SELECT cs.customer_id,cs.package_id,p.name,p.monthly_price FROM customer_services cs LEFT JOIN packages p ON p.id=cs.package_id WHERE cs.tenant_id=:t AND cs.id=:s AND cs.status='active' FOR UPDATE");$s->execute([':t'=>$tenantId,':s'=>$serviceId]);$r=$s->fetch();if(!$r||$r['package_id']===null)throw new RuntimeException('Billable service/package not found.');
   $invoiceNo='INV-'.date('Ym').'-'.bin2hex(random_bytes(5));
   $i=$pdo->prepare('INSERT INTO invoices(tenant_id,customer_id,service_id,invoice_no,due_date,subtotal,total) VALUES(:t,:c,:s,:n,:d,:a,:a) RETURNING id');$i->execute([':t'=>$tenantId,':c'=>$r['customer_id'],':s'=>$serviceId,':n'=>$invoiceNo,':d'=>$dueDate,':a'=>(int)$r['monthly_price']]);$id=(int)$i->fetchColumn();
   $x=$pdo->prepare('INSERT INTO invoice_items(invoice_id,description,quantity,unit_price,amount) VALUES(:i,:d,1,:p,:p)');$x->execute([':i'=>$id,':d'=>$r['name']??'Internet Service',':p'=>(int)$r['monthly_price']]);$pdo->commit();return $id;
  } catch(\Throwable $e){$pdo->rollBack();throw $e;}
 }
}
