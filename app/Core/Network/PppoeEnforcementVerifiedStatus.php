<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final class PppoeEnforcementVerifiedStatus
{
 public const SUCCESS='success'; public const FAILED='failed'; public const MISMATCH='mismatch';
 public static function from(array $before,array $after,string $action,?string $targetProfile):string
 {
  if($action==='none') return self::SUCCESS;
  $enabledOk=true;$profileOk=true;
  if(in_array($action,['enable','disable'],true))$enabledOk=($after['enabled']??null)===($action==='enable');
  if(in_array($action,['apply_suspend_profile','restore_profile'],true))$profileOk=($after['profile']??null)===$targetProfile;
  return $enabledOk&&$profileOk?self::SUCCESS:self::MISMATCH;
 }
}
