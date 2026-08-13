<?php

declare(strict_types=1);
namespace Ispluka\Core\Import;
final class MikroTikPppImportMapper
{
 public function map(array $secret):array{return['pppoe_username'=>trim((string)($secret['name']??'')),'password'=>$secret['password']??null,'service'=>'pppoe','package'=>$secret['profile']??null,'enabled'=>!filter_var($secret['disabled']??false,FILTER_VALIDATE_BOOLEAN),'caller_id'=>$secret['caller-id']??$secret['caller_id']??null,'local_address'=>$secret['local-address']??$secret['local_address']??null,'remote_address'=>$secret['remote-address']??$secret['remote_address']??null,'comment'=>$secret['comment']??null,'source'=>'mikrotik'];}
 public function classify(array $mapped):string{return trim((string)$mapped['pppoe_username'])===''?'invalid':(($mapped['package']===null||trim((string)$mapped['package'])==='')?'incomplete':'ready');}
}
