<?php

declare(strict_types=1);

namespace Ispluka\Controllers;

use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Database\Database;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
use Ispluka\Core\Security\Csrf;
use PDO;
use Throwable;

final class CollectionController
{
    public function __construct(private readonly Database $db, private readonly AuthManager $auth, private readonly Csrf $csrf) {}
    public function collectionPage(): Response { return $this->view('collection.php', ['csrf'=>$this->csrf->token()]); }
    public function reportPage(Request $request): Response {
        $from=(string)$request->query('from',date('Y-m-01')); $to=(string)$request->query('to',date('Y-m-d')); $method=trim((string)$request->query('method','')); $search=trim((string)$request->query('search','')); $tenant=$this->tenant();
        $where="p.tenant_id=:tenant AND p.status='completed' AND p.paid_at::date BETWEEN :from AND :to"; $params=['tenant'=>$tenant,'from'=>$from,'to'=>$to];
        if($method!==''){ $where.=' AND p.method=:method'; $params['method']=$method; }
        if($search!==''){ $where.=' AND (c.name ILIKE :search OR c.customer_code ILIKE :search OR c.phone ILIKE :search OR p.reference ILIKE :search)'; $params['search']='%'.$search.'%'; }
        $s=$this->db->pdo()->prepare("SELECT p.id,p.reference,p.amount,p.method,p.paid_at,c.customer_code,c.name AS customer_name,i.invoice_number FROM payments p JOIN customers c ON c.id=p.customer_id LEFT JOIN invoices i ON i.id=p.invoice_id WHERE {$where} ORDER BY p.paid_at DESC,p.id DESC LIMIT 500"); $s->execute($params); $rows=$s->fetchAll(PDO::FETCH_ASSOC);
        $total=array_sum(array_map(static fn(array $r): float => (float)$r['amount'],$rows)); return $this->view('collection-report.php',['rows'=>$rows,'total'=>$total,'from'=>$from,'to'=>$to,'method'=>$method,'search'=>$search]);
    }
    public function customerCreatePage(): Response { return $this->view('customer-create.php',['csrf'=>$this->csrf->token()]); }
    public function customerSearchPage(): Response { return $this->view('customer-search.php',['csrf'=>$this->csrf->token()]); }
    public function customerInvoices(Request $request): Response {
        try{$customerId=(int)$request->query('customer_id',0);$tenant=$this->tenant();$s=$this->db->pdo()->prepare("SELECT i.id,i.invoice_number,i.issue_date,i.due_date,i.total,i.paid_amount,i.status,GREATEST(i.total-i.paid_amount,0) AS due FROM invoices i WHERE i.tenant_id=:tenant AND i.customer_id=:customer AND i.status IN ('unpaid','partial','overdue') ORDER BY i.due_date ASC,i.id ASC");$s->execute(['tenant'=>$tenant,'customer'=>$customerId]);return Response::json(['data'=>$s->fetchAll(PDO::FETCH_ASSOC)]);}catch(Throwable $e){return Response::json(['error'=>['message'=>$e->getMessage()]],422);}
    }
    public function collect(Request $request): Response {
        try{
            if(!$this->csrf->validate((string)$request->input('_csrf','')))return Response::json(['error'=>['message'=>'Invalid CSRF token.']],419);
            $tenant=$this->tenant();$customer=(int)$request->input('customer_id',0);$invoice=(int)$request->input('invoice_id',0);$amount=(float)$request->input('amount',0);$method=strtolower(trim((string)$request->input('method','cash')));$reference=trim((string)$request->input('reference',''));
            if($customer<=0||$invoice<=0||$amount<=0)throw new \InvalidArgumentException('Customer, invoice and a positive amount are required.');
            if(!in_array($method,['cash','bank','bkash','nagad','card','other'],true))throw new \InvalidArgumentException('Invalid payment method.');
            if($reference==='')$reference='COL-'.date('YmdHis').'-'.strtoupper(bin2hex(random_bytes(3)));$pdo=$this->db->pdo();$pdo->beginTransaction();
            try{$lock=$pdo->prepare("SELECT id,total,paid_amount FROM invoices WHERE tenant_id=:tenant AND id=:invoice AND customer_id=:customer AND status IN ('unpaid','partial','overdue') FOR UPDATE");$lock->execute(['tenant'=>$tenant,'invoice'=>$invoice,'customer'=>$customer]);$inv=$lock->fetch(PDO::FETCH_ASSOC);if(!$inv)throw new \RuntimeException('Invoice not found or is no longer payable.');$due=max(0,(float)$inv['total']-(float)$inv['paid_amount']);if($amount>$due)throw new \InvalidArgumentException('Collection cannot exceed invoice due amount of ৳'.number_format($due,2).'.');$ins=$pdo->prepare("INSERT INTO payments(tenant_id,customer_id,invoice_id,reference,method,amount,status,paid_at,metadata) VALUES(:tenant,:customer,:invoice,:reference,:method,:amount,'completed',CURRENT_TIMESTAMP,'{}'::jsonb) RETURNING id");$ins->execute(['tenant'=>$tenant,'customer'=>$customer,'invoice'=>$invoice,'reference'=>$reference,'method'=>$method,'amount'=>$amount]);$paymentId=(int)$ins->fetchColumn();$alloc=$pdo->prepare('INSERT INTO payment_allocations(payment_id,invoice_id,amount) VALUES(:payment,:invoice,:amount)');$alloc->execute(['payment'=>$paymentId,'invoice'=>$invoice,'amount'=>$amount]);$newPaid=(float)$inv['paid_amount']+$amount;$status=$newPaid+0.00001>=(float)$inv['total']?'paid':'partial';$up=$pdo->prepare('UPDATE invoices SET paid_amount=:paid,status=:status,updated_at=CURRENT_TIMESTAMP WHERE id=:invoice AND tenant_id=:tenant');$up->execute(['paid'=>$newPaid,'status'=>$status,'invoice'=>$invoice,'tenant'=>$tenant]);$pdo->commit();return Response::json(['data'=>['payment_id'=>$paymentId,'reference'=>$reference,'amount'=>$amount,'invoice_status'=>$status]],201);}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
        }catch(Throwable $e){return Response::json(['error'=>['message'=>$e->getMessage()]],422);}
    }
    private function tenant(): int{$tenant=$this->auth->tenantId();if($tenant===null||$tenant<=0)throw new \RuntimeException('Tenant context is required.');return $tenant;}
    private function view(string $file,array $data): Response{$path=dirname(__DIR__,2).'/resources/views/'.$file;if(!is_file($path))throw new \RuntimeException('View missing: '.$file);extract($data,EXTR_SKIP);ob_start();require $path;return Response::text((string)ob_get_clean());}
}
