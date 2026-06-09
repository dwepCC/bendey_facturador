<?php

declare(strict_types=1);

namespace App\Service\Gre;

use App\Service\ConfigProviderInterface;

/**
 * URLs REST GRE según ambiente de la empresa (infraestructura vía .env).
 */
final class GreEndpointResolver
{
    /**
     * @return array{auth: string, cpe: string}
     */
    public static function resolveForAmbiente(string $ambiente, ConfigProviderInterface $config): array
    {
        $isProd = strtolower(trim($ambiente)) === 'produccion';

        if ($isProd) {
            $auth = trim((string) ($config->get('PRO_AUTH_URL') ?? ''));
            $cpe = trim((string) ($config->get('PRO_API_URL') ?? ''));
            if ($auth === '') {
                $auth = 'https://api-seguridad.sunat.gob.pe/v1';
            }
            if ($cpe === '') {
                $cpe = 'https://api-cpe.sunat.gob.pe/v1';
            }

            return ['auth' => rtrim($auth, '/'), 'cpe' => rtrim($cpe, '/')];
        }

        $auth = trim((string) ($config->get('AUTH_URL') ?? ''));
        $cpe = trim((string) ($config->get('API_URL') ?? ''));
        if ($auth === '') {
            $auth = 'https://gre-test.nubefact.com/v1';
        }
        if ($cpe === '') {
            $cpe = 'https://gre-test.nubefact.com/v1';
        }

        return ['auth' => rtrim($auth, '/'), 'cpe' => rtrim($cpe, '/')];
    }
}
