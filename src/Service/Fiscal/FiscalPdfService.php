<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use App\Service\ConfigProviderInterface;
use App\Service\FileDataReader;
use App\Service\SeeFactory;
use Greenter\Model\DocumentInterface;
use Greenter\Report\ReportInterface;
use Greenter\Report\XmlUtils;
use Psr\Log\LoggerInterface;

/**
 * Genera PDF fiscal vía Greenter Report (wkhtmltopdf).
 */
class FiscalPdfService
{
    private ReportInterface $report;
    private SeeFactory $seeFactory;
    private ConfigProviderInterface $fileProvider;
    private FileDataReader $fileReader;
    private LoggerInterface $logger;

    public function __construct(
        ReportInterface $report,
        SeeFactory $seeFactory,
        ConfigProviderInterface $fileProvider,
        FileDataReader $fileReader,
        LoggerInterface $logger
    ) {
        $this->report = $report;
        $this->seeFactory = $seeFactory;
        $this->fileProvider = $fileProvider;
        $this->fileReader = $fileReader;
        $this->logger = $logger;
    }

  /**
   * @param class-string $documentClass
   */
    public function render(string $documentClass, DocumentInterface $document, string $signedXml, ?string $hashOverride = null): ?string
    {
        if (!FiscalDocumentClassResolver::supportsPdf($documentClass) || $signedXml === '') {
            return null;
        }
        try {
            $ruc = trim((string) $document->getCompany()->getRuc());
            $see = $this->seeFactory->build($documentClass, $ruc);
            $hash = $hashOverride !== null && $hashOverride !== ''
                ? $hashOverride
                : (new XmlUtils())->getHashSign($signedXml);
            $parameters = [
                'system' => [
                    'logo' => $this->resolveLogo($ruc),
                    'hash' => $hash,
                ],
                'user' => [
                    'header' => '',
                    'extras' => [],
                ],
            ];
            $pdf = $this->report->render($document, $parameters);
            return is_string($pdf) && $pdf !== '' ? $pdf : null;
        } catch (\Throwable $e) {
            $this->logger->warning('fiscal_pdf_render_failed', [
                'class' => $documentClass,
                'error' => $e->getMessage(),
            ]);
            throw new FiscalPdfRenderException($e->getMessage(), 0, $e);
        }
    }

    private function resolveLogo(string $ruc): ?string
    {
        $jsonCompanies = $this->fileProvider->get('companies');
        if ($jsonCompanies === '') {
            return null;
        }
        $companies = json_decode($jsonCompanies, true);
        if (!is_array($companies) || !isset($companies[$ruc]['logo'])) {
            return null;
        }
        $logoFile = (string) $companies[$ruc]['logo'];
        if ($logoFile === '') {
            return null;
        }
        try {
            return $this->fileReader->getContents($logoFile);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
