<?php

declare(strict_types=1);

namespace App\Service\Gre;

use App\Entity\Empresa;
use App\Service\ConfigProviderInterface;

/**
 * Resuelve client_id / client_secret OAuth GRE por empresa.
 * Fallback Nubefact Test solo en ambiente pruebas.
 */
final class GreOAuthCredentialResolver
{
    public const SCOPE = 'https://api-cpe.sunat.gob.pe';

    /**
     * @return array{client_id: string, client_secret: string, source: string}
     */
    public static function resolve(Empresa $empresa, ConfigProviderInterface $config): array
    {
        $clientId = trim((string) ($empresa->getGreClientId() ?? ''));
        $clientSecret = trim((string) ($empresa->getGreClientSecret() ?? ''));

        if ($clientId !== '' && $clientSecret !== '') {
            return [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'source' => 'empresa',
            ];
        }

        if (strtolower(trim($empresa->getAmbiente())) === 'produccion') {
            throw new \InvalidArgumentException(
                'GRE OAuth no configurado para RUC ' . $empresa->getRuc() . ' en ambiente producción'
            );
        }

        $fallbackId = trim((string) ($config->get('CLIENT_ID') ?? ''));
        $fallbackSecret = trim((string) ($config->get('CLIENT_SECRET') ?? ''));
        if ($fallbackId === '' || $fallbackSecret === '') {
            throw new \InvalidArgumentException(
                'GRE OAuth no configurado y fallback Nubefact Test ausente en .env (CLIENT_ID/CLIENT_SECRET)'
            );
        }

        return [
            'client_id' => $fallbackId,
            'client_secret' => $fallbackSecret,
            'source' => 'nubefact_test_fallback',
        ];
    }

    public static function isConfigured(Empresa $empresa, ConfigProviderInterface $config): bool
    {
        if (trim((string) ($empresa->getGreClientId() ?? '')) !== ''
            && trim((string) ($empresa->getGreClientSecret() ?? '')) !== '') {
            return true;
        }

        if (strtolower(trim($empresa->getAmbiente())) !== 'produccion') {
            $fallbackId = trim((string) ($config->get('CLIENT_ID') ?? ''));
            $fallbackSecret = trim((string) ($config->get('CLIENT_SECRET') ?? ''));

            return $fallbackId !== '' && $fallbackSecret !== '';
        }

        return false;
    }
}
