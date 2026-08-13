<?php

declare(strict_types=1);
namespace Ispluka\Core\Auth;
final class PasswordResetToken {
 public static function generate():array { $raw=bin2hex(random_bytes(32)); return [$raw,hash('sha256',$raw)]; }
 public static function hash(string $token):string{return hash('sha256',$token);}
}
