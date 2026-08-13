<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
use InvalidArgumentException;
final class PppoeImportCompletionValidator
{
 public function validate(PppoeImportCandidate $candidate,int $customerId):void{if($customerId<1)throw new InvalidArgumentException('A valid customer is required.');if($candidate->status==='completed')throw new InvalidArgumentException('Import candidate is already completed.');if(trim($candidate->username)==='')throw new InvalidArgumentException('PPPoE username is required.');}
}
