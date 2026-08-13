<?php

declare(strict_types=1);
namespace Ispluka\Core\Customers;
use Ispluka\Core\Database\Database;
use RuntimeException;
final class CustomerService {
 public function __construct(private readonly Database $db) {}
 public function create(int $tenantId,array $data):int {
  $name=trim((string)($data['name']??'')); if($name==='') throw new RuntimeException('Customer name is required.');
  $s=$this->db->pdo()->prepare('INSERT INTO customers (tenant_id,name,phone,email,address,status) VALUES (:t,:n,:p,:e,:a,:s) RETURNING id');
  $s->execute([':t'=>$tenantId,':n'=>$name,':p'=>trim((string)($data['phone']??'')),':e'=>trim((string)($data['email']??'')),':a'=>trim((string)($data['address']??'')),':s'=>'active']); return (int)$s->fetchColumn();
 }
 public function find(int $tenantId,int $id):?array { $s=$this->db->pdo()->prepare('SELECT * FROM customers WHERE tenant_id=:t AND id=:id'); $s->execute([':t'=>$tenantId,':id'=>$id]); $r=$s->fetch(); return $r?:null; }
 public function list(int $tenantId,int $limit=50,int $offset=0):array { $limit=min(max($limit,1),100); $offset=max($offset,0); $s=$this->db->pdo()->prepare('SELECT id,name,phone,email,status,created_at FROM customers WHERE tenant_id=:t ORDER BY id DESC LIMIT :l OFFSET :o'); $s->bindValue(':t',$tenantId);$s->bindValue(':l',$limit,\PDO::PARAM_INT);$s->bindValue(':o',$offset,\PDO::PARAM_INT);$s->execute();return $s->fetchAll(); }
}
