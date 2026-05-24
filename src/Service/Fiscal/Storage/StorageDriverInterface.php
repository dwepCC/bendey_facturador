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
}
