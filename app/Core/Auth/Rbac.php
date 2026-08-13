<?php

declare(strict_types=1);
namespace Ispluka\Core\Auth;
final class Rbac {
 public function allows(array $roles,string $permission):bool {
  $map=['master_admin'=>['*'],'admin'=>['dashboard.view','customers.*','packages.*','routers.*','billing.*','payments.*','reports.*'],'reseller'=>['dashboard.view','customers.view','customers.create','billing.view','payments.view'],'employee'=>['dashboard.view','customers.view','billing.view','payments.create'],'customer'=>['dashboard.view','invoices.view','payments.create']];
  foreach($roles as $role){foreach($map[$role]??[] as $grant){if($grant==='*'||$grant===$permission||str_ends_with($grant,'.*')&&str_starts_with($permission,substr($grant,0,-1))) return true;}}
  return false;
 }
}
