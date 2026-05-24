<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Storage;

use Aws\S3\S3Client;

/**
 * Storage S3-compatible (AWS S3, Cloudflare R2, MinIO).
 */
class S3CompatibleStorageDriver implements StorageDriverInterface
{
    private S3Client $client;
    private string $bucket;
    private string $publicBaseUrl;
    private string $keyPrefix;

    public function __construct(
        string $region,
        string $endpoint,
        string $bucket,
        string $accessKey,
        string $secretKey,
        string $publicBaseUrl,
        string $keyPrefix = ''
    ) {
        $config = [
            'version' => 'latest',
            'region' => $region !== '' ? $region : 'auto',
            'credentials' => [
                'key' => $accessKey,
                'secret' => $secretKey,
            ],
        ];
        if ($endpoint !== '') {
            $config['endpoint'] = $endpoint;
            $config['use_path_style_endpoint'] = true;
        }
        $this->client = new S3Client($config);
        $this->bucket = $bucket;
        $this->publicBaseUrl = rtrim($publicBaseUrl, '/');
        $this->keyPrefix = trim($keyPrefix, '/');
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
        $baseName = sprintf('%s-%s-%s', $documentType, $series, $number);

        $unsignedUrl = null;
        if ($unsignedXml !== null && $unsignedXml !== '') {
            $unsignedUrl = $this->put($relDir . '/' . $baseName . '-unsigned.xml', $unsignedXml, 'application/xml');
        }
        $signedUrl = $this->put($relDir . '/' . $baseName . '.xml', $signedXml, 'application/xml');
        $cdrUrl = null;
        if ($cdrZip !== null && $cdrZip !== '') {
            $cdrUrl = $this->put($relDir . '/' . $baseName . '-cdr.zip', $cdrZip, 'application/zip');
        }
        $pdfUrl = null;
        if ($pdf !== null && $pdf !== '') {
            $pdfUrl = $this->put($relDir . '/' . $baseName . '.pdf', $pdf, 'application/pdf');
        }

        return [
            'xml_url' => $signedUrl,
            'unsigned_xml_url' => $unsignedUrl,
            'xml_signed_url' => $signedUrl,
            'cdr_url' => $cdrUrl,
            'pdf_url' => $pdfUrl,
        ];
    }

    private function put(string $relativePath, string $body, string $contentType): string
    {
        $key = $this->keyPrefix !== '' ? $this->keyPrefix . '/' . $relativePath : $relativePath;
        $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'Body' => $body,
            'ContentType' => $contentType,
        ]);
        return $this->publicBaseUrl . '/' . ltrim($key, '/');
    }

    private function sanitize(string $slug): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '-', $slug) ?: 'unknown';
    }
}
