<?php

declare(strict_types=1);

use Ispluka\Core\Security\SecretBox;

it('encrypts and decrypts secrets without exposing plaintext', function (): void {
    $box = new SecretBox('test-key');
    $secret = 'mikrotik-password-123';
    $cipher = $box->encrypt($secret);
    expect($cipher)->not->toContain($secret);
    expect($box->decrypt($cipher))->toBe($secret);
});
