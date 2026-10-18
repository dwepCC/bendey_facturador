<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Verification;

final class CurlValidaPseConsultClient implements ValidaPseConsultClientInterface
{
    public function consultar(string $baseUrl, string $token, string $nombreArchivo): array
    {
        $base = rtrim($baseUrl, '/');
        if (substr($base, -4) === '/api') {
            $base = substr($base, 0, -4);
        }
        $ch = curl_init($base . '/api/cpe/consultar/' . rawurlencode($nombreArchivo));
        if ($ch === false) {
            return ['http' => 0, 'body' => ''];
        }
        curl_setopt_array($ch, [
            CURLOPT_HTTPGET => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Authorization: Bearer ' . $token],
        ]);
        $body = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['http' => $http, 'body' => $body === false ? '' : (string) $body];
    }
}
