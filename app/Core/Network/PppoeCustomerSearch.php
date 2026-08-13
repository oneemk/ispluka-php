<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use PDO;
final class PppoeCustomerSearch
{
 public function __construct(private readonly PDO $pdo){}
 public function find(int $tenantId,string $term,int $limit=20):array{$term=trim($term);$limit=max(1,min(50,$limit));$q=$this->pdo->prepare("SELECT id,name,phone,pppoe_username FROM customers WHERE tenant_id=:t AND (pppoe_username ILIKE :q OR phone ILIKE :q OR name ILIKE :q) ORDER BY name LIMIT ".$limit);$q->execute([':t'=>$tenantId,':q'=>'%'.$term.'%']);return$q->fetchAll(PDO::FETCH_ASSOC);}
}
