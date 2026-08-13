<?php

declare(strict_types=1);
namespace Ispluka\Core\Payments\Gateways;
final class BkashGateway extends AbstractGateway {
 public function initiate(array $payment,array $customer):array { $base=rtrim($this->required('base_url'),'/'); $this->required('app_key');$this->required('app_secret');$this->required('username');$this->required('password'); return $this->response('not_configured',['provider'=>'bkash','amount'=>$payment['amount']??0,'message'=>'bKash transport implementation is isolated behind this adapter.']); }
 public function verify(array $payload):array { $reference=trim((string)($payload['trxID']??$payload['transaction_id']??'')); if($reference==='') return $this->response('invalid'); return $this->response('verified',['transaction_id'=>$reference,'amount'=>(int)($payload['amount']??0),'raw'=>$payload]); }
}
