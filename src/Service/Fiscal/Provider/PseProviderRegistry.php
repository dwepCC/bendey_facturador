<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Provider;

/**
 * Catálogo interno de proveedores PSE (URL base por proveedor).
 */
final class PseProviderRegistry
{
    public static function normalizeProvider(string $provider): string
    {
        $p = strtolower(trim($provider));
        if ($p === '' || $p === 'pse') {
            return 'validapse';
        }

        return $p;
    }

    public static function baseUrl(string $provider): string
    {
        return match (self::normalizeProvider($provider)) {
            'validapse' => 'https://app.validapse.com',
            default => '',
        };
    }
}
