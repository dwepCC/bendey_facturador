<?php

declare(strict_types=1);

namespace App\Service;

use Exception;
use Greenter\Model\DocumentInterface;
use Greenter\Report\PdfReport;
use Greenter\Report\ReportInterface;

class PdfReportDecorator implements ReportInterface
{
    /**
     * @var ReportInterface
     */
    private $htmlReport;

    /**
     * @var string
     */
    private $wkhtmlBin;
    /**
     * @var array
     */
    private $options;

    /**
     * PdfReportDecorator constructor.
     *
     * @param ReportInterface $htmlReport
     * @param string $wkhtmlBin
     * @param array $options
     * @param string|null $projectDir Directorio raíz del proyecto (para fallback en Windows)
     */
    public function __construct(ReportInterface $htmlReport, string $wkhtmlBin, array $options, ?string $projectDir = null)
    {
        $this->htmlReport = $htmlReport;
        $this->wkhtmlBin = $this->resolveBinPath($wkhtmlBin, $projectDir);
        $this->options = $options;
    }

    public function render(DocumentInterface $document, array $parameters = []): ?string
    {
        $reporter = $this->createPdfReport();

        $pdf = $reporter->render($document, $parameters);

        if ($pdf == null) {
            $error = $reporter->getExporter()->getError();
            $hint = ' Compruebe que wkhtmltopdf está instalado y que WKHTMLTOPDF_PATH en .env apunta al ejecutable. '
                . 'En Windows use la ruta completa (ej. C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe o '
                . $this->wkhtmlBin . ').';
            throw new Exception($error . $hint);
        }

        return $pdf;
    }

    private function createPdfReport(): PdfReport
    {
        $pdfReport = new PdfReport($this->htmlReport);
        $pdfReport->setBinPath($this->wkhtmlBin);
        $pdfReport->setOptions($this->options);

        return $pdfReport;
    }

    /**
     * Resuelve la ruta de wkhtmltopdf: .env → vendor/bin → PATH del sistema.
     */
    private function resolveBinPath(string $wkhtmlBin, ?string $projectDir): string
    {
        $wkhtmlBin = trim($wkhtmlBin, " \t\n\r\0\x0B\"'");
        $candidates = [];

        if ($wkhtmlBin !== '' && $wkhtmlBin !== 'wkhtmltopdf') {
            if ($projectDir !== null && $projectDir !== '' && !preg_match('#^[a-zA-Z]:[/\\\\]|^/#', $wkhtmlBin)) {
                $candidates[] = $projectDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $wkhtmlBin);
            }
            $candidates[] = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $wkhtmlBin);
        }

        if ($projectDir !== null && $projectDir !== '') {
            $candidates[] = $projectDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'wkhtmltopdf.exe';
            $candidates[] = $projectDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'wkhtmltopdf';
        }

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && is_file($candidate)) {
                $real = realpath($candidate);

                return $real !== false ? $real : $candidate;
            }
        }

        return $wkhtmlBin !== '' ? $wkhtmlBin : 'wkhtmltopdf';
    }
}