<?php

declare(strict_types=1);

namespace Ispluka\Services;

use Ispluka\Repositories\InvoiceRepository;
use InvalidArgumentException;

final class BillingService
{
    public function __construct(private readonly InvoiceRepository $invoices) {}

    public function createInvoice(int $tenantId, int $customerId, string $invoiceNumber, string $issueDate, string $dueDate, array $items, float $discount=0.0, float $tax=0.0): int
    {
        if ($tenantId<=0 || $customerId<=0 || $invoiceNumber==='' || $items===[] || $discount<0 || $tax<0) {
            throw new InvalidArgumentException('Invalid invoice data.');
        }
        $normalized=[]; $subtotal=0.0;
        foreach ($items as $item) {
            $qty=(float)($item['quantity']??1); $unit=(float)($item['unit_price']??0); $lineDiscount=(float)($item['discount']??0); $lineTax=(float)($item['tax']??0);
            if (trim((string)($item['description']??''))==='' || $qty<=0 || $unit<0 || $lineDiscount<0 || $lineTax<0) throw new InvalidArgumentException('Invalid invoice item.');
            $line=max(0, round($qty*$unit-$lineDiscount+$lineTax,2));
            $subtotal += round($qty*$unit,2);
            $normalized[]=['service_id'=>$item['service_id']??null,'description'=>trim((string)$item['description']),'quantity'=>$qty,'unit_price'=>number_format($unit,2,'.',''),'discount'=>number_format($lineDiscount,2,'.',''),'tax'=>number_format($lineTax,2,'.',''),'total'=>number_format($line,2,'.','')];
        }
        $total=max(0, round($subtotal-$discount+$tax,2));
        return $this->invoices->create($tenantId,$customerId,['invoice_number'=>$invoiceNumber,'issue_date'=>$issueDate,'due_date'=>$dueDate,'subtotal'=>number_format($subtotal,2,'.',''),'discount'=>number_format($discount,2,'.',''),'tax'=>number_format($tax,2,'.',''),'total'=>number_format($total,2,'.',''),'status'=>'unpaid','metadata'=>[]],$normalized);
    }
}
