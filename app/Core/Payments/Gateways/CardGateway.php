<?php

declare(strict_types=1);
namespace Ispluka\Core\Payments\Gateways;
final class CardGateway extends AbstractGateway {
 public function initiate(array $payment,array $customer):array { $this->required('base_url');$this->required('merchant_id');$this->required('api_key');return$this->response('not_configured',['provider'=>'card','amount'=>$payment['amount']??0,'message'=>'Card processor transport is isolated behind this adapter.']); }
 public function verify(array $payload):array { $reference=trim((string)($payload['transaction_id']??$payload['tran_id']??''));if($reference==='')return$this->response('invalid');return$this->response('verified',['transaction_id'=>$reference,'amount'=>(int)($payload['amount']??0),'raw'=>$payload]); }
}
