<?php

declare(strict_types=1);
use Ispluka\Database\Migrations\MigrationInterface;
return new class implements MigrationInterface{public function up(PDO $pdo):void{$pdo->exec("DELETE FROM role_permissions rp USING roles r, permissions p WHERE rp.role_id=r.id AND rp.permission_id=p.id AND r.code='admin' AND p.code='tenant.manage'");}public function down(PDO $pdo):void{}};
