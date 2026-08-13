<?php

declare(strict_types=1);
namespace Ispluka\Core\Inventory;
use Ispluka\Core\Database\Database;
use RuntimeException;
final class InventoryService {
 public function __construct(private readonly Database $db) {}
 public function move(int $tenantId,int $itemId,float $quantity,string $type,?string $referenceType=null,?int $referenceId=null,?string $note=null):void{$quantity=round($quantity,3);if($quantity<=0)throw new RuntimeException('Quantity must be positive.');$pdo=$this->db->pdo();$pdo->beginTransaction();try{$s=$pdo->prepare('SELECT stock_qty FROM inventory_items WHERE tenant_id=:t AND id=:i FOR UPDATE');$s->execute([':t'=>$tenantId,':i'=>$itemId]);$stock=$s->fetchColumn();if($stock===false)throw new RuntimeException('Inventory item not found.');$delta=in_array($type,['in','return','adjust_in'],true)?$quantity:-$quantity;$new=(float)$stock+$delta;if($new<0)throw new RuntimeException('Insufficient stock.');$m=$pdo->prepare('INSERT INTO inventory_movements(tenant_id,item_id,type,quantity,reference_type,reference_id,note) VALUES(:t,:i,:ty,:q,:rt,:ri,:n)');$m->execute([':t'=>$tenantId,':i'=>$itemId,':ty'=>$type,':q'=>$delta,':rt'=>$referenceType,':ri'=>$referenceId,':n'=>$note]);$u=$pdo->prepare('UPDATE inventory_items SET stock_qty=:q,updated_at=CURRENT_TIMESTAMP WHERE tenant_id=:t AND id=:i');$u->execute([':q'=>$new,':t'=>$tenantId,':i'=>$itemId]);$pdo->commit();}catch(\Throwable $e){$pdo->rollBack();throw$e;}}
 public function lowStock(int $tenantId):array{$s=$this->db->pdo()->prepare('SELECT id,sku,name,stock_qty,reorder_level FROM inventory_items WHERE tenant_id=:t AND active AND stock_qty<=reorder_level ORDER BY name');$s->execute([':t'=>$tenantId]);return$s->fetchAll();}
}
