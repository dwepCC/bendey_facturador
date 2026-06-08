<?php

declare(strict_types=1);

namespace App\Service\Fiscal\EmpresaDestroy;

use App\Entity\Empresa;

/**
 * Datos mínimos para purgar Redis y almacenamiento, incluso tras eliminar la fila empresa.
 */
final class EmpresaDestroyPurgeContext
{
    /**
     * @param string[] $documentUuids
     * @param string[] $fingerprints
     */
    public function __construct(
        public readonly string $ruc,
        public readonly ?string $tenantSlug,
        public readonly ?int $tenantId,
        public readonly ?string $certificateFile,
        public readonly ?string $logoFile,
        public readonly array $documentUuids = [],
        public readonly array $fingerprints = [],
    ) {
    }

    public static function fromEmpresa(Empresa $empresa, array $documentUuids = [], array $fingerprints = []): self
    {
        return new self(
            ruc: trim($empresa->getRuc()),
            tenantSlug: self::nullableString($empresa->getTenantSlug()),
            tenantId: $empresa->getTenantId(),
            certificateFile: self::nullableString($empresa->getCertificate()),
            logoFile: self::nullableString($empresa->getLogo()),
            documentUuids: $documentUuids,
            fingerprints: $fingerprints,
        );
    }

    public static function forOrphanCleanup(string $ruc, ?string $tenantSlug = null, ?int $tenantId = null): self
    {
        $ruc = preg_replace('/\D/', '', trim($ruc)) ?? '';

        return new self(
            ruc: $ruc,
            tenantSlug: self::nullableString($tenantSlug),
            tenantId: $tenantId !== null && $tenantId > 0 ? $tenantId : null,
            certificateFile: $ruc !== '' ? $ruc . '-cert.pem' : null,
            logoFile: $ruc !== '' ? $ruc . '-logo.png' : null,
        );
    }

    public function withDocumentData(array $documentUuids, array $fingerprints): self
    {
        return new self(
            ruc: $this->ruc,
            tenantSlug: $this->tenantSlug,
            tenantId: $this->tenantId,
            certificateFile: $this->certificateFile,
            logoFile: $this->logoFile,
            documentUuids: $documentUuids,
            fingerprints: $fingerprints,
        );
    }

    private static function nullableString(?string $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
