<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use App\Service\Fiscal\Storage\StorageDriverInterface;

/**
 * Fachada de almacenamiento fiscal (driver local/S3/R2).
 */
class FiscalStorageService
{
    private StorageDriverInterface $driver;

    public function __construct(StorageDriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @return array{xml_url: string, unsigned_xml_url: ?string, xml_signed_url: string, cdr_url: ?string, pdf_url: ?string}
     */
    public function store(
        string $tenantSlug,
        string $documentType,
        string $series,
        string $number,
        ?string $unsignedXml,
        string $signedXml,
        ?string $cdrZip = null,
        ?string $pdf = null
    ): array {
        return $this->driver->storeFiscalFiles(
            $tenantSlug,
            $documentType,
            $series,
            $number,
            $unsignedXml,
            $signedXml,
            $cdrZip,
            $pdf
        );
    }
}
