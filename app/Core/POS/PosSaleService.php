<?php

declare(strict_types=1);
namespace Ispluka\Core\POS;
use Ispluka\Core\Database\Database;
use Ispluka\Core\Inventory\InventoryService;
use RuntimeException;
final class PosSaleService {
 public function __construct(private readonly Database $db,private readonly InventoryService $inventory) {}
 public function create(int $tenantId,array $items,?int $customerId=null,?int $userId=null,int $discount=0):int{$pdo=$this->db->pdo();if(!$items)throw new RuntimeException('POS sale requires items.');$pdo->beginTransaction();try{$subtotal=0;$validated=[];foreach($items as $item){$id=(int)$item['item_id'];$qty=(float)$item['quantity'];$s=$pdo->prepare('SELECT sale_price FROM inventory_items WHERE tenant_id=:t AND id=:i AND active FOR UPDATE');$s->execute([':t'=>$tenantId,':i'=>$id]);$price=$s->fetchColumn();if($price===false||$qty<=0)throw new RuntimeException('Invalid POS item.');$line=(int)$price*$qty;$subtotal+=$line;$validated[]=['id'=>$id,'qty'=>$qty,'price'=>(int)$price,'line'=>(int)$line];}$total=max(0,$subtotal-$discount);$s=$pdo->prepare('INSERT INTO pos_sales(tenant_id,customer_id,subtotal,discount,total,created_by) VALUES(:t,:c,:s,:d,:total,:u) RETURNING id');$s->execute([':t'=>$tenantId,':c'=>$customerId,':s'=>$subtotal,':d'=>$discount,':total'=>$total,':u'=>$userId]);$saleId=(int)$s->fetchColumn();foreach($validated as $v){$i=$pdo->prepare('INSERT INTO pos_sale_items(sale_id,item_id,quantity,unit_price,line_total) VALUES(:s,:i,:q,:p,:l)');$i->execute([':s'=>$saleId,':i'=>$v['id'],':q'=>$v['qty'],':p'=>$v['price'],':l'=>$v['line']]);$this->inventory->move($tenantId,$v['id'],$v['qty'],'out','pos_sale',$saleId);} $pdo->commit();return$saleId;}catch(\Throwable $e){$pdo->rollBack();throw$e;}}
}
