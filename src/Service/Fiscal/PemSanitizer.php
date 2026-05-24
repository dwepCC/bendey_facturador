<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

/**
 * Normaliza PEM al formato que Greenter setCertificate() espera al persistir en disco.
 * Corrige exportaciones OpenSSL/PFX con metadatos embebidos o claves PKCS#1 mal etiquetadas.
 */
final class PemSanitizer
{
    public static function normalizeCombined(string $pem): string
    {
        $pem = str_replace("\r\n", "\n", trim($pem));
        if ($pem === '') {
            return '';
        }

        $parts = [];
        $offset = 0;
        while (preg_match('/-----BEGIN ([A-Z0-9 ]+)-----/', $pem, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $type = $m[1][0];
            $start = $m[0][1];
            $begin = '-----BEGIN ' . $type . '-----';
            $end = '-----END ' . $type . '-----';
            $endPos = strpos($pem, $end, $start);
            if ($endPos === false) {
                break;
            }

            $body = substr($pem, $start + strlen($begin), $endPos - $start - strlen($begin));
            $b64 = self::extractBase64Body($body);
            if ($b64 === '') {
                $offset = $endPos + strlen($end);
                continue;
            }

            [$outType, $outB64] = self::normalizeKeyBlock($type, $b64);
            $outBegin = '-----BEGIN ' . $outType . '-----';
            $outEnd = '-----END ' . $outType . '-----';
            $parts[] = $outBegin . "\n" . chunk_split($outB64, 64, "\n") . $outEnd;

            $offset = $endPos + strlen($end);
        }

        if ($parts === []) {
            return $pem;
        }

        return implode("\n", $parts);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function normalizeKeyBlock(string $type, string $b64): array
    {
        if ($type !== 'PRIVATE KEY') {
            return [$type, $b64];
        }

        $pem = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($b64, 64, "\n") . "-----END PRIVATE KEY-----";
        if (openssl_pkey_get_private($pem) !== false) {
            return ['PRIVATE KEY', $b64];
        }

        $rsaPem = "-----BEGIN RSA PRIVATE KEY-----\n" . chunk_split($b64, 64, "\n") . "-----END RSA PRIVATE KEY-----";
        if (openssl_pkey_get_private($rsaPem) !== false) {
            return ['RSA PRIVATE KEY', $b64];
        }

        return ['PRIVATE KEY', $b64];
    }

    private static function extractBase64Body(string $body): string
    {
        $lines = [];
        foreach (explode("\n", $body) as $line) {
            $line = trim($line);
            if ($line === '' || str_contains($line, ':')) {
                continue;
            }
            $lines[] = $line;
        }

        return implode('', $lines);
    }
}
