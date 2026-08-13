<?php

declare(strict_types=1);
namespace Ispluka\Core\Network;
interface MikrotikClientInterface { public function connect(array $router):void; public function command(string $command,array $arguments=[]):array; public function disconnect():void; }
