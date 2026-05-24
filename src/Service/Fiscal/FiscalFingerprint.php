<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

/**
 * Huella idempotente: tenant + tipo + serie + número + sale_id.
 */
final class FiscalFingerprint
{
    public static function build(int $tenantId, string $docType, string $series, string $number, int $saleId): string
    {
        $raw = implode('|', [
            (string) $tenantId,
            strtolower(trim($docType)),
            strtoupper(trim($series)),
            trim($number),
            (string) $saleId,
        ]);
        return hash('sha256', $raw);
    }
}
