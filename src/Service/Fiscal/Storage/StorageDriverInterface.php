<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Storage;

/**
 * Abstracción de almacenamiento fiscal (local, S3, R2, MinIO).
 */
interface StorageDriverInterface
{
    /**
     * @return array{xml_url: string, unsigned_xml_url: ?string, xml_signed_url: string, cdr_url: ?string, pdf_url: ?string}
     */
    public function storeFiscalFiles(
        string $tenantSlug,
        string $documentType,
        string $series,
        string $number,
        ?string $unsignedXml,
        string $signedXml,
        ?string $cdrZip,
        ?string $pdf
    ): array;

    /**
     * Guarda SOLO el CDR (zip) de un comprobante y devuelve su URL. Para la verificación manual
     * en SUNAT: el XML firmado ya está (o no existe) y storeFiscalFiles lo sobrescribiría.
     */
    public function storeCdr(string $tenantSlug, string $documentType, string $series, string $number, string $cdrZip): string;
}
