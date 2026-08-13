<?php

declare(strict_types=1);
namespace Ispluka\Core\Import;
final class CustomerFieldDetector
{
 private const MAP=['name'=>['name','customer name','customer_name','full name','fullname','নাম','গ্রাহকের নাম'],'mobile'=>['mobile','mobile no','mobile number','phone','phone number','contact','মোবাইল','মোবাইল নম্বর','ফোন'],'pppoe_username'=>['pppoe','pppoe username','username','user name','login','pppoe user','ইউজারনেম','পিপিপিওই'],'package'=>['package','plan','profile','speed','প্যাকেজ','প্রোফাইল','স্পিড'],'address'=>['address','location','ঠিকানা','এলাকা'],'nid'=>['nid','national id','nid number','নিদ','জাতীয় পরিচয়পত্র'],'email'=>['email','email address','ইমেইল'],'password'=>['password','pass','পাসওয়ার্ড'],'reseller'=>['reseller','dealer','রিসেলার','ডিলার']];
 public function detect(array $headers,array $rows=[]):array{$result=[];foreach($headers as $index=>$header){$key=$this->normalize((string)$header);$best=null;$score=0;foreach(self::MAP as $field=>$aliases){foreach($aliases as $alias){$a=$this->normalize($alias);$s=$key===$a?100:(str_contains($key,$a)||str_contains($a,$key)?70:0);if($s>$score){$score=$s;$best=$field;}}}if(!$best&&$rows)$best=$this->inferFromValues($rows,$index);$result[$index]=['field'=>$best,'confidence'=>$score];}return$result;}
 private function normalize(string $v):string{$v=trim(mb_strtolower($v));return preg_replace('/[\s_\-\.]+/u',' ',$v)??$v;}
 private function inferFromValues(array $rows,int $index):?string{$values=[];foreach(array_slice($rows,0,20) as $row)if(isset($row[$index]))$values[]=(string)$row[$index];$mobile=0;$email=0;foreach($values as $v){$x=preg_replace('/\D+/','',$v)??'';if(strlen($x)>=10&&strlen($x)<=15)$mobile++;if(filter_var(trim($v),FILTER_VALIDATE_EMAIL))$email++;}if($email>=max(1,count($values)/2))return'email';if($mobile>=max(1,count($values)/2))return'mobile';return null;}
}
