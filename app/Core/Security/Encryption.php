<?php

declare(strict_types=1);

namespace Ispluka\Core\Security;

use RuntimeException;

final class Encryption
{
    private const CIPHER = 'aes-256-gcm';

    public function __construct(private readonly string $key)
    {
        if ($key === '' || $key === 'change_me') {
            throw new RuntimeException('APP_KEY must be configured before encryption is used.');
        }
    }

    public function encrypt(string $plaintext): string
    {
        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $this->key(), OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($ciphertext === false) throw new RuntimeException('Encryption failed.');
        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt(string $payload): string
    {
        $raw = base64_decode($payload, true);
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        if ($raw === false || strlen($raw) <= $ivLength + 16) throw new RuntimeException('Invalid encrypted payload.');
        $iv = substr($raw, 0, $ivLength);
        $tag = substr($raw, $ivLength, 16);
        $ciphertext = substr($raw, $ivLength + 16);
        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $this->key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($plaintext === false) throw new RuntimeException('Decryption failed.');
        return $plaintext;
    }

    private function key(): string
    {
        return hash('sha256', $this->key, true);
    }
}
