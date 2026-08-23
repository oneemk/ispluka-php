<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use Ispluka\Core\Security\SecretBox;
use PHPUnit\Framework\TestCase;

final class SecretBoxTest extends TestCase
{
    public function testEncryptsAndDecryptsSecretsWithoutExposingPlaintext(): void
    {
        $box = new SecretBox('test-key');
        $secret = 'mikrotik-password-123';
        $cipher = $box->encrypt($secret);

        self::assertStringNotContainsString($secret, $cipher);
        self::assertSame($secret, $box->decrypt($cipher));
    }
}
