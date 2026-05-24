<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

/**
 * Valida PEM en el mismo criterio que Greenter/XMLSecurityKey (openssl_pkey_get_private).
 */
final class PemCertificateValidator
{
    public static function assertSignable(string $pem): void
    {
        $pem = PemSanitizer::normalizeCombined(trim($pem));
        if ($pem === '') {
            throw new \InvalidArgumentException('Certificado vacío');
        }
        if (strpos($pem, 'BEGIN CERTIFICATE') === false) {
            throw new \InvalidArgumentException('El archivo debe incluir un bloque CERTIFICATE');
        }
        $hasPrivateBlock = strpos($pem, 'PRIVATE KEY') !== false || strpos($pem, 'RSA PRIVATE KEY') !== false;
        if (!$hasPrivateBlock) {
            throw new \InvalidArgumentException('El archivo debe incluir clave privada (PRIVATE KEY) para firmar XML ante SUNAT');
        }

        $keyPem = self::extractPrivateKeyPem($pem);
        if ($keyPem === null) {
            throw new \InvalidArgumentException('No se encontró un bloque de clave privada válido en el PEM');
        }
        if (strpos($keyPem, 'ENCRYPTED PRIVATE KEY') !== false) {
            throw new \InvalidArgumentException('La clave privada está encriptada; suba el PFX con contraseña o un PEM sin encriptar');
        }

        $key = openssl_pkey_get_private($keyPem);
        if ($key === false) {
            throw new \InvalidArgumentException('La clave privada no es usable para firmar (openssl_pkey_get_private falló)');
        }
    }

    private static function extractPrivateKeyPem(string $pem): ?string
    {
        foreach (['PRIVATE KEY', 'RSA PRIVATE KEY', 'EC PRIVATE KEY', 'ENCRYPTED PRIVATE KEY'] as $type) {
            $begin = '-----BEGIN ' . $type . '-----';
            $end = '-----END ' . $type . '-----';
            $i = strpos($pem, $begin);
            if ($i === false) {
                continue;
            }
            $j = strpos($pem, $end, $i);
            if ($j === false) {
                continue;
            }

            return substr($pem, $i, $j - $i + strlen($end));
        }

        return null;
    }
}
