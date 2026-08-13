<?php

declare(strict_types=1);

namespace Ispluka\Core\Payments\Gateways;

use RuntimeException;

abstract class HttpGateway extends AbstractGateway
{
    protected function request(string $url, string $method, array $headers = [], ?string $body = null): array
    {
        $ch = curl_init($url);
        if ($ch === false) throw new RuntimeException('Unable to initialize payment HTTP client.');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($raw === false || $error !== '') throw new RuntimeException('Payment gateway request failed.');
        $decoded = json_decode((string)$raw, true);
        if (!is_array($decoded)) throw new RuntimeException('Payment gateway returned an invalid response.');
        $decoded['_http_status'] = $status;
        return $decoded;
    }
}
