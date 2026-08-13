<?php

declare(strict_types=1);
namespace Ispluka\Core\Automation;
use Ispluka\Core\Billing\InvoiceService;
use Ispluka\Core\Billing\OverdueProcessor;
use Ispluka\Core\Database\Database;
final class BillingAutomation {
 public function __construct(private readonly Database $db,private readonly InvoiceService $invoices,private readonly OverdueProcessor $overdue,private readonly CronLock $lock) {}
 public function run():array { $name='ispluka.billing.daily';if(!$this->lock->acquire($name))return['locked'=>true];$created=0;$overdue=0;try{$s=$this->db->pdo()->query("SELECT tenant_id,id FROM customer_services WHERE status='active' AND next_billing_at<=CURRENT_TIMESTAMP ORDER BY id LIMIT 500");foreach($s->fetchAll() as $r){$this->invoices->createForService((int)$r['tenant_id'],(int)$r['id'],date('Y-m-d',strtotime('+7 days')));$this->db->pdo()->prepare("UPDATE customer_services SET next_billing_at=next_billing_at + INTERVAL '1 month',updated_at=CURRENT_TIMESTAMP WHERE id=:id AND tenant_id=:t")->execute([':id'=>$r['id'],':t'=>$r['tenant_id']]);$created++;}$overdue=$this->overdue->markOverdue();return['locked'=>false,'invoices_created'=>$created,'overdue'=>$overdue];}finally{$this->lock->release($name);}}
}
