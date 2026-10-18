<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Verification;

/**
 * Token: POST api-seguridad.sunat.gob.pe/v1/clientesextranet/{client_id}/oauth2/token/ con
 * grant_type=client_credentials y scope fijo; dura ~1 h y se reutiliza mientras el proceso viva.
 * Consulta: POST api.sunat.gob.pe/v1/contribuyente/contribuyentes/{ruc}/validarcomprobante.
 */
final class CurlSunatValidezApiClient implements SunatValidezApiClientInterface
{
    private const SCOPE = 'https://api.sunat.gob.pe/v1/contribuyente/contribuyentes';

    /** @var array<string, array{token: string, expires: int}> */
    private array $tokens = [];

    public function validar(string $clientId, string $clientSecret, string $rucConsulta, array $consulta): array
    {
        $token = $this->token($clientId, $clientSecret);
        if ($token === null) {
            return ['http' => 401, 'body' => '{"success":false,"message":"No se pudo obtener el token de la API de SUNAT (revise client_id y client_secret)."}'];
        }

        return $this->request(
            'https://api.sunat.gob.pe/v1/contribuyente/contribuyentes/' . rawurlencode($rucConsulta) . '/validarcomprobante',
            json_encode($consulta, JSON_UNESCAPED_UNICODE) ?: '{}',
            ['Content-Type: application/json', 'Accept: application/json', 'Authorization: Bearer ' . $token]
        );
    }

    private function token(string $clientId, string $clientSecret): ?string
    {
        $cached = $this->tokens[$clientId] ?? null;
        if ($cached !== null && $cached['expires'] > time() + 60) {
            return $cached['token'];
        }
        $resp = $this->request(
            'https://api-seguridad.sunat.gob.pe/v1/clientesextranet/' . rawurlencode($clientId) . '/oauth2/token/',
            http_build_query([
                'grant_type' => 'client_credentials',
                'scope' => self::SCOPE,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]),
            ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json']
        );
        $json = json_decode($resp['body'], true);
        $token = is_array($json) ? (string) ($json['access_token'] ?? '') : '';
        if ($token === '') {
            return null;
        }
        $this->tokens[$clientId] = ['token' => $token, 'expires' => time() + (int) ($json['expires_in'] ?? 3600)];

        return $token;
    }

    /**
     * @param string[] $headers
     * @return array{http: int, body: string}
     */
    private function request(string $url, string $body, array $headers): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return ['http' => 0, 'body' => ''];
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $out = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['http' => $http, 'body' => $out === false ? '' : (string) $out];
    }
}
