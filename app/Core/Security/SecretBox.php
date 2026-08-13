<?php

declare(strict_types=1);

namespace Ispluka\Core\Security;

use RuntimeException;

final class SecretBox
{
    private const CIPHER = 'aes-256-gcm';

    public function __construct(private readonly string $keyMaterial)
    {
        if ($this->keyMaterial === '') {
            throw new RuntimeException('Encryption key is not configured.');
        }
    }

    public function encrypt(string $plaintext): string
    {
        $key = hash('sha256', $this->keyMaterial, true);
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext === false) {
            throw new RuntimeException('Unable to encrypt secret.');
        }

        return base64_encode(json_encode([
            'v' => 1,
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'data' => base64_encode($ciphertext),
        ], JSON_THROW_ON_ERROR));
    }

    public function decrypt(string $payload): string
    {
        try {
            $decoded = json_decode(base64_decode($payload, true) ?: '', true, 512, JSON_THROW_ON_ERROR);
            $key = hash('sha256', $this->keyMaterial, true);
            $plaintext = openssl_decrypt(
                base64_decode((string) $decoded['data'], true),
                self::CIPHER,
                $key,
                OPENSSL_RAW_DATA,
                base64_decode((string) $decoded['iv'], true),
                base64_decode((string) $decoded['tag'], true)
            );
        } catch (\Throwable $e) {
            throw new RuntimeException('Unable to decrypt secret.', 0, $e);
        }

        if ($plaintext === false) {
            throw new RuntimeException('Unable to decrypt secret.');
        }

        return $plaintext;
    }
}
