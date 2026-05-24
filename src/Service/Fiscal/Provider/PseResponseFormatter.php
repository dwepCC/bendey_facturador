<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Provider;

/**
 * Normaliza mensajes y resumen de respuestas PSE (ValidaPSE, etc.).
 */
final class PseResponseFormatter
{
    /**
     * @param array<string, mixed> $resp
     */
    public static function message(array $resp): string
    {
        foreach (['mensaje', 'message', 'errors', 'error'] as $key) {
            if (!empty($resp[$key]) && is_string($resp[$key])) {
                return trim($resp[$key]);
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed>|null $resp
     * @return array<string, mixed>|null
     */
    public static function summarize(?array $resp): ?array
    {
        if ($resp === null || $resp === []) {
            return null;
        }

        return [
            'isSuccess' => $resp['isSuccess'] ?? null,
            'estado' => $resp['estado'] ?? null,
            'mensaje' => self::message($resp),
            'codigo_hash' => $resp['codigo_hash'] ?? null,
            'external_id' => $resp['external_id'] ?? null,
        ];
    }
}
