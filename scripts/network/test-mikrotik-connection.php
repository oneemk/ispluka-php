<?php

declare(strict_types=1);

use Ispluka\Core\Network\MikrotikConnectionClient;
use Ispluka\Core\Network\RouterOsApiClient;
use Ispluka\Core\Network\RouterOsSshClient;

$root=dirname(__DIR__,2);require $root.'/vendor/autoload.php';
if($argc<5){fwrite(STDERR,"Usage: php scripts/network/test-mikrotik-connection.php METHOD HOST PORT USER [PASSWORD] [SSL]\nMETHOD=api|ssh\n");exit(2);}
$method=strtolower(trim((string)$argv[1]));$host=trim((string)$argv[2]);$port=(int)$argv[3];$user=(string)$argv[4];$password=$argc>=6?(string)$argv[5]:'';$ssl=$argc>=7&&in_array(strtolower((string)$argv[6]),['1','true','yes','ssl'],true);
if(!in_array($method,['api','ssh'],true)||$host===''||$port<1||$port>65535||$user===''){fwrite(STDERR,"Invalid METHOD/HOST/PORT/USER.\n");exit(2);}
$source=trim((string)@shell_exec('curl -4 -sS --max-time 5 https://api.ipify.org 2>/dev/null'));if($source!=='')echo "Source public IPv4: {$source}\n";echo "Method: ".strtoupper($method)."\nTarget: {$host}:{$port}\nTCP preflight: ";
$errno=0;$errstr='';$socket=@stream_socket_client("tcp://{$host}:{$port}",$errno,$errstr,5.0,STREAM_CLIENT_CONNECT);if(!is_resource($socket)){echo "FAILED\nerrno={$errno}\nerror=".trim($errstr)."\nThis failure occurs before RouterOS authentication.\n";exit(10);}fclose($socket);echo "OK\n";
$client=new MikrotikConnectionClient(new RouterOsApiClient(),new RouterOsSshClient());try{echo "RouterOS login: ";$client->connect(['host'=>$host,'connection_method'=>$method,'api_port'=>$port,'ssh_port'=>$port,'username'=>$user,'password'=>$password,'api_ssl'=>$ssl,'verify_ssl'=>false]);echo "OK\n";echo "Identity: ";$rows=$client->command('/system/identity/print');echo (($rows[0]['name']??'(unknown)'))."\n";echo "Resource: ";$resource=$client->command('/system/resource/print');echo json_encode($resource[0]??[],JSON_UNESCAPED_SLASHES)."\n";echo "RESULT: MikroTik {$method} connection is working.\n";exit(0);}catch(Throwable $e){echo "FAILED\nerror=".$e->getMessage()."\nRESULT: MikroTik {$method} connection failed.\n";exit(11);}finally{try{$client->disconnect();}catch(Throwable $ignore){}}
