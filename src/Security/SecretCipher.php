<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Cifrado en reposo de credenciales de empresa (clave SOL, secretos OAuth de GRE y de la API de
 * validez de SUNAT). AES-256-GCM; la clave sale de FISCAL_SECRETS_KEY (cualquier texto largo, p. ej.
 * `openssl rand -base64 32`; se deriva con SHA-256).
 *
 * Formato guardado: "enc:v1:" + base64(iv[12] + tag[16] + cifrado). Un valor sin ese prefijo es
 * texto plano heredado: se lee tal cual y se cifra al volver a guardarse (o con
 * `app:fiscal:encrypt-secrets`).
 */
final class SecretCipher
{
    public const PREFIX = 'enc:v1:';
    private const CIPHER = 'aes-256-gcm';

    public static function isEncrypted(?string $value): bool
    {
        return $value !== null && str_starts_with($value, self::PREFIX);
    }

    public static function isConfigured(): bool
    {
        return self::rawKey() !== '';
    }

    /**
     * Sin clave configurada devuelve el texto plano: no se corta la facturación por un despliegue
     * sin la variable. Se avisa en el log de PHP y `app:fiscal:encrypt-secrets` lo reporta.
     */
    public static function encrypt(?string $plain): ?string
    {
        if ($plain === null || $plain === '' || self::isEncrypted($plain)) {
            return $plain;
        }
        $key = self::key();
        if ($key === null) {
            error_log('[fiscal] FISCAL_SECRETS_KEY no configurada: credencial guardada SIN cifrar');

            return $plain;
        }
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($cipher === false) {
            throw new \RuntimeException('No se pudo cifrar la credencial');
        }

        return self::PREFIX . base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(?string $stored): ?string
    {
        if (!self::isEncrypted($stored)) {
            return $stored;
        }
        $key = self::key();
        if ($key === null) {
            throw new \RuntimeException('Hay credenciales cifradas y FISCAL_SECRETS_KEY no está configurada');
        }
        $raw = base64_decode(substr((string) $stored, strlen(self::PREFIX)), true);
        if ($raw === false || strlen($raw) < 29) {
            throw new \RuntimeException('Credencial cifrada corrupta');
        }
        $plain = openssl_decrypt(substr($raw, 28), self::CIPHER, $key, OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
        if ($plain === false) {
            throw new \RuntimeException('No se pudo descifrar la credencial (¿cambió FISCAL_SECRETS_KEY?)');
        }

        return $plain;
    }

    private static function key(): ?string
    {
        $raw = self::rawKey();

        return $raw === '' ? null : hash('sha256', $raw, true);
    }

    private static function rawKey(): string
    {
        $v = $_SERVER['FISCAL_SECRETS_KEY'] ?? $_ENV['FISCAL_SECRETS_KEY'] ?? getenv('FISCAL_SECRETS_KEY');

        return is_string($v) ? trim($v) : '';
    }
}
