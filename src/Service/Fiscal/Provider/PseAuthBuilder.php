<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Provider;

use App\Entity\Empresa;

/**
 * Construye headers HTTP según connection_type de la empresa.
 */
class PseAuthBuilder
{
    /**
     * @return string[]
     */
    public function buildHeaders(Empresa $empresa, array $extra = []): array
    {
        $type = strtolower(trim($empresa->getConnectionType()));
        $headers = array_merge([
            'Content-Type: application/json',
            'Accept: application/json',
        ], $extra);

        if ($type === 'basic_auth') {
            $user = trim((string) ($empresa->getPseUser() ?? ''));
            $pass = trim((string) ($empresa->getPsePass() ?? ''));
            if ($user === '' || $pass === '') {
                throw new \RuntimeException('PSE basic_auth requiere pse_user y pse_password');
            }
            $headers[] = 'Authorization: Basic ' . base64_encode($user . ':' . $pass);
            return $headers;
        }

        if ($type === 'custom') {
            $meta = $this->decodeMetadata($empresa);
            foreach ($meta['headers'] ?? [] as $k => $v) {
                if (is_string($k) && is_string($v)) {
                    $headers[] = $k . ': ' . $v;
                }
            }
            return $headers;
        }

        // bearer (default)
        $token = $empresa->resolvePseToken();
        if ($token === '') {
            throw new \RuntimeException('Token PSE no configurado');
        }
        $headers[] = 'Authorization: Bearer ' . $token;
        return $headers;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeMetadata(Empresa $empresa): array
    {
        $raw = trim((string) ($empresa->getPseMetadataJson() ?? ''));
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
