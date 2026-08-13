<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
final class PppoeEnforcementStateDiff
{
 public function compare(array $before,array $after):array{$keys=['enabled','profile','service','remote_address'];$diff=[];foreach($keys as $key){$b=$before[$key]??null;$a=$after[$key]??null;if($b!==$a)$diff[$key]=['before'=>$b,'after'=>$a];}return$diff;}
}
