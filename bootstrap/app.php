<?php

declare(strict_types=1);

use Ispluka\Controllers\Auth\LoginController;
use Ispluka\Controllers\CustomerController;
use Ispluka\Controllers\CustomerServiceController;
use Ispluka\Controllers\MikrotikEnforcementAuditController;
use Ispluka\Controllers\MikrotikManualActionController;
use Ispluka\Core\Application;
use Ispluka\Core\Auth\AuthManager;
use Ispluka\Core\Auth\Authorization;
use Ispluka\Core\Auth\Session;
use Ispluka\Core\Database\Database;
use Ispluka\Core\Environment;
use Ispluka\Core\Exceptions\Handler;
use Ispluka\Core\Http\Request;
use Ispluka\Core\Http\Response;
use Ispluka\Core\Routing\Router;
use Ispluka\Core\Security\Csrf;
use Ispluka\Core\Security\Encryption;
use Ispluka\Core\Security\SecretBox;
use Ispluka\Core\Network\MikrotikAutomationService;
use Ispluka\Core\Network\MikrotikClientInterface;
use Ispluka\Core\Network\PppoeEnforcementAuditQuery;
use Ispluka\Middleware\Authorize;
use Ispluka\Repositories\CustomerRepository;
use Ispluka\Repositories\CustomerServiceRepository;
use Ispluka\Services\CustomerAccessService;
use Ispluka\Services\CustomerService;

require_once dirname(__DIR__) . '/vendor/autoload.php';
$root=dirname(__DIR__);$environmentFile=$root.'/.env';if(is_file($environmentFile))Environment::load($environmentFile);
$database=new Database(require $root.'/config/database.php');$session=new Session();$auth=new AuthManager($database,$session);$authorization=new Authorization($database,$auth);$authorize=new Authorize($authorization);$csrf=new Csrf($session);$encryption=new Encryption((string)($_ENV['APP_KEY']??''));
$router=new Router();$exceptionHandler=new Handler();$loginController=new LoginController($auth,$session,$csrf);$customerController=new CustomerController(new CustomerService(new CustomerRepository($database)),$auth);$customerServiceController=new CustomerServiceController(new CustomerAccessService(new CustomerServiceRepository($database)),$auth,$encryption);$mikrotikEnforcementAuditController=new MikrotikEnforcementAuditController($database->pdo());
$mikrotikClient=defined('ISPLUKA_MIKROTIK_CLIENT')?ISPLUKA_MIKROTIK_CLIENT:null;if(!$mikrotikClient instanceof MikrotikClientInterface){$mikrotikClient=new class implements MikrotikClientInterface{public function connect(array $router):void{}public function command(string $command,array $args=[]):array{return['command'=>$command,'args'=>$args];}public function disconnect():void{}};}
$secretBox=new SecretBox((string)($_ENV['APP_KEY']??''));$automation=new MikrotikAutomationService($database,$secretBox,$mikrotikClient);$mikrotikManualActionController=new MikrotikManualActionController($database->pdo(),$auth,$automation,new PppoeEnforcementAuditQuery($database->pdo()));
$csrfMiddleware=static function(Request $request,callable $next)use($csrf):Response{if(!$csrf->validate($request->input('_csrf')))return Response::json(['error'=>['message'=>'Invalid CSRF token.']],419);return $next($request);};
$webRoutes=$root.'/routes/web.php';if(is_file($webRoutes)){$registerRoutes=require $webRoutes;if(is_callable($registerRoutes))$registerRoutes($router,$loginController,$auth,$csrf,$authorize,$customerController,$customerServiceController,$csrfMiddleware,$mikrotikEnforcementAuditController,$mikrotikManualActionController);}
return new Application($router,$exceptionHandler);
