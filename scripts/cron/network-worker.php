<?php

declare(strict_types=1);

use Ispluka\Core\Automation\NetworkJobWorker;
use Ispluka\Core\Database\Database;
use Ispluka\Core\Environment;
use Ispluka\Core\Network\MikrotikAutomationService;
use Ispluka\Core\Network\RouterOsApiClient;
use Ispluka\Core\Security\SecretBox;

require dirname(__DIR__, 2) . '/vendor/autoload.php';
$root=dirname(__DIR__,2);$env=$root.'/.env';if(is_file($env))Environment::load($env);
$lock=fopen(sys_get_temp_dir().'/ispluka-network-worker.lock','c');if($lock===false||!flock($lock,LOCK_EX|LOCK_NB)){fwrite(STDOUT,"Network worker skipped: another run is active.\n");exit(0);}
try{$db=new Database(require $root.'/config/database.php');$secret=new SecretBox((string)($_ENV['APP_KEY']??''));$client=new RouterOsApiClient();$automation=new MikrotikAutomationService($db,$secret,$client);$worker=new NetworkJobWorker($db,$automation);$result=$worker->run(isset($argv[1])?(int)$argv[1]:20);printf("Network jobs completed: %d; failed/requeued: %d\n",(int)$result['completed'],(int)$result['failed_or_requeued']);}catch(Throwable $e){fwrite(STDERR,"Network worker failed: ".substr($e->getMessage(),0,1000)."\n");exit(1);}finally{flock($lock,LOCK_UN);fclose($lock);}
