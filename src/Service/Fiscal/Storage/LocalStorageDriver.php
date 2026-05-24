<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Storage;

/**
 * Driver local — preparado para reemplazar por S3/R2 sin cambiar callers.
 */
class LocalStorageDriver implements StorageDriverInterface
{
    private string $basePath;
    private string $publicBaseUrl;

    public function __construct(string $basePath, string $publicBaseUrl)
    {
        $this->basePath = rtrim($basePath, '/\\');
        $this->publicBaseUrl = rtrim($publicBaseUrl, '/');
    }

    public function storeFiscalFiles(
        string $tenantSlug,
        string $documentType,
        string $series,
        string $number,
        ?string $unsignedXml,
        string $signedXml,
        ?string $cdrZip,
        ?string $pdf
    ): array {
        $now = new \DateTimeImmutable();
        $relDir = sprintf(
            '%s/sunat/%s/%s',
            $this->sanitize($tenantSlug),
            $now->format('Y'),
            $now->format('m')
        );
        $absDir = $this->basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relDir);
        if (!is_dir($absDir) && !mkdir($absDir, 0755, true) && !is_dir($absDir)) {
            throw new \RuntimeException('No se pudo crear directorio: ' . $absDir);
        }

        $baseName = sprintf('%s-%s-%s', $documentType, $series, $number);
        $unsignedUrl = null;

        if ($unsignedXml !== null && $unsignedXml !== '') {
            $f = $baseName . '-unsigned.xml';
            file_put_contents($absDir . DIRECTORY_SEPARATOR . $f, $unsignedXml);
            $unsignedUrl = $this->publicBaseUrl . '/' . $relDir . '/' . $f;
        }

        $signedFile = $baseName . '.xml';
        file_put_contents($absDir . DIRECTORY_SEPARATOR . $signedFile, $signedXml);
        $signedUrl = $this->publicBaseUrl . '/' . $relDir . '/' . $signedFile;

        $cdrUrl = null;
        if ($cdrZip !== null && $cdrZip !== '') {
            $cdrFile = $baseName . '-cdr.zip';
            file_put_contents($absDir . DIRECTORY_SEPARATOR . $cdrFile, $cdrZip);
            $cdrUrl = $this->publicBaseUrl . '/' . $relDir . '/' . $cdrFile;
        }

        $pdfUrl = null;
        if ($pdf !== null && $pdf !== '') {
            $pdfFile = $baseName . '.pdf';
            file_put_contents($absDir . DIRECTORY_SEPARATOR . $pdfFile, $pdf);
            $pdfUrl = $this->publicBaseUrl . '/' . $relDir . '/' . $pdfFile;
        }

        return [
            'xml_url' => $signedUrl,
            'unsigned_xml_url' => $unsignedUrl,
            'xml_signed_url' => $signedUrl,
            'cdr_url' => $cdrUrl,
            'pdf_url' => $pdfUrl,
        ];
    }

    private function sanitize(string $slug): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '-', $slug) ?: 'unknown';
    }
}
