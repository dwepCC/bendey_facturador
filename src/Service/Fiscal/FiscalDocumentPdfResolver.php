<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use App\Entity\FiscalDocument;
use Doctrine\ORM\EntityManagerInterface;
use Greenter\Model\DocumentInterface;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

/**
 * Resuelve PDF fiscal almacenado o lo genera on-demand (wkhtmltopdf) desde XML firmado + snapshot.
 * Aplica a SUNAT directo y PSE por igual.
 */
class FiscalDocumentPdfResolver
{
    private FiscalFileFetcher $fileFetcher;
    private FiscalPdfService $pdfService;
    private SerializerInterface $serializer;
    private EntityManagerInterface $em;
    private LoggerInterface $logger;
    private string $localStoragePath;

    public function __construct(
        FiscalFileFetcher $fileFetcher,
        FiscalPdfService $pdfService,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        string $localStoragePath
    ) {
        $this->fileFetcher = $fileFetcher;
        $this->pdfService = $pdfService;
        $this->serializer = $serializer;
        $this->em = $em;
        $this->logger = $logger;
        $this->localStoragePath = rtrim($localStoragePath, '/\\');
    }

    public function hasStoredPdf(FiscalDocument $doc): bool
    {
        $url = trim((string) ($doc->getPdfUrl() ?? ''));
        if ($url === '') {
            return false;
        }
        return $this->fileFetcher->fetch($url) !== null;
    }

    public function canGenerate(FiscalDocument $doc): bool
    {
        try {
            [$class] = $this->deserialize($doc);
            if (!FiscalDocumentClassResolver::supportsPdf($class)) {
                return false;
            }
        } catch (\Throwable) {
            return false;
        }

        return $this->loadSignedXml($doc) !== null;
    }

    /**
     * Devuelve bytes PDF; persiste en storage si se generó on-demand.
     */
    public function resolve(FiscalDocument $doc, bool $persist = true): ?string
    {
        $stored = $this->fileFetcher->fetch($doc->getPdfUrl());
        if ($stored !== null) {
            return $stored['content'];
        }

        return $this->generate($doc, $persist);
    }

    public function generate(FiscalDocument $doc, bool $persist = true): ?string
    {
        $signedXml = $this->loadSignedXml($doc);
        if ($signedXml === null || $signedXml === '') {
            return null;
        }

        try {
            [$class, $greenterDoc] = $this->deserialize($doc);
            if (!FiscalDocumentClassResolver::supportsPdf($class)) {
                return null;
            }
        } catch (\Throwable $e) {
            $this->logger->warning('fiscal_pdf_deserialize_failed', [
                'uuid' => $doc->getDocumentUuid(),
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        $hash = trim((string) ($doc->getHash() ?? ''));
        $pdf = $this->pdfService->render(
            $class,
            $greenterDoc,
            $signedXml,
            $hash !== '' ? $hash : null
        );
        if ($pdf === null || $pdf === '') {
            throw new FiscalPdfRenderException('wkhtmltopdf no produjo bytes PDF');
        }

        if ($persist) {
            $pdfUrl = $this->persistBesideSignedXml($doc, $pdf);
            if ($pdfUrl !== null) {
                $doc->setPdfUrl($pdfUrl);
                $this->em->flush();
            }
        }

        return $pdf;
    }

    private function loadSignedXml(FiscalDocument $doc): ?string
    {
        foreach ([$doc->getXmlSignedUrl(), $doc->getXmlUrl()] as $url) {
            $url = trim((string) ($url ?? ''));
            if ($url === '') {
                continue;
            }
            $fetched = $this->fileFetcher->fetch($url);
            if ($fetched !== null && $fetched['content'] !== '') {
                return $fetched['content'];
            }
        }

        return null;
    }

    /**
     * @return array{0: string, 1: DocumentInterface}
     */
    private function deserialize(FiscalDocument $doc): array
    {
        $raw = $doc->getSnapshotJson();
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException('snapshot_json inválido');
        }
        if (isset($data['document']) && is_array($data['document'])) {
            $data = $data['document'];
        }
        $class = FiscalDocumentClassResolver::resolve($data, $doc);
        $greenterDoc = $this->serializer->deserialize(json_encode($data), $class, 'json');

        return [$class, $greenterDoc];
    }

    private function persistBesideSignedXml(FiscalDocument $doc, string $pdf): ?string
    {
        $signedUrl = trim((string) ($doc->getXmlSignedUrl() ?? $doc->getXmlUrl() ?? ''));
        if ($signedUrl === '') {
            return null;
        }

        $localSigned = $this->resolveLocalPath($signedUrl);
        if ($localSigned === null) {
            $this->logger->warning('fiscal_pdf_persist_skip_remote_only', [
                'uuid' => $doc->getDocumentUuid(),
                'signed_url' => $signedUrl,
            ]);
            return null;
        }

        $localPdf = preg_replace('/\.xml$/i', '.pdf', $localSigned);
        if ($localPdf === null || $localPdf === $localSigned) {
            $localPdf = $localSigned . '.pdf';
        }

        if (file_put_contents($localPdf, $pdf) === false) {
            return null;
        }

        $publicPdf = preg_replace('/\.xml$/i', '.pdf', $signedUrl);
        if ($publicPdf === null || $publicPdf === $signedUrl) {
            return $signedUrl . '.pdf';
        }

        return $publicPdf;
    }

    private function resolveLocalPath(string $url): ?string
    {
        $parts = parse_url($url);
        if (!isset($parts['path'])) {
            return null;
        }
        $path = ltrim((string) $parts['path'], '/');
        if (strpos($path, 'fiscal-files/') === 0) {
            $path = substr($path, strlen('fiscal-files/'));
        }
        $full = $this->localStoragePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

        return is_file($full) ? $full : null;
    }
}
