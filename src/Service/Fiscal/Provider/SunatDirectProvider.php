<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Provider;

use App\Entity\Empresa;
use App\Entity\FiscalDocument;
use App\Service\SeeFactory;
use App\Service\Fiscal\PemCertificateValidator;
use Greenter\Model\Despatch\Despatch;
use Greenter\Model\DocumentInterface;
use Greenter\Model\Response\CdrResponse;
use Greenter\Report\XmlUtils;

/**
 * SUNAT directo: Greenter genera XML, firma con certificado local, envía a SUNAT.
 */
class SunatDirectProvider extends AbstractFiscalProvider
{
    private SeeFactory $seeFactory;
    private string $dataPath;

    public function __construct(SeeFactory $seeFactory, string $dataPath)
    {
        $this->seeFactory = $seeFactory;
        $this->dataPath = $dataPath;
    }

    public function getName(): string
    {
        return 'sunat_direct';
    }

    public function supports(FiscalDocument $doc, Empresa $empresa): bool
    {
        $tipo = strtoupper(trim((string) $doc->getDocumentType()));
        if (in_array($tipo, ['09', '31'], true)) {
            return false;
        }

        $mode = strtolower(trim((string) ($doc->getSendMode() ?? $empresa->getSendMode())));
        return $mode === '' || $mode === 'sunat_direct' || $mode === 'sunat';
    }

    public function validateConnection(Empresa $empresa): FiscalConnectionResult
    {
        $ruc = $empresa->getRuc();
        if (trim($empresa->getSolUser()) === '' || trim($empresa->getSolPass()) === '') {
            return FiscalConnectionResult::fail('invalid_credentials', 'Usuario o clave SOL no configurados');
        }
        $certFile = $empresa->getCertificate();
        if ($certFile === null || trim($certFile) === '') {
            return FiscalConnectionResult::fail('configuration_missing', 'Certificado digital no configurado');
        }
        $certPath = $this->dataPath . DIRECTORY_SEPARATOR . $certFile;
        if (!is_file($certPath)) {
            return FiscalConnectionResult::fail('configuration_missing', 'Archivo de certificado no encontrado: ' . $certFile);
        }
        $content = file_get_contents($certPath);
        if ($content === false) {
            return FiscalConnectionResult::fail('error', 'No se pudo leer el certificado');
        }
        try {
            PemCertificateValidator::assertSignable($content);
        } catch (\InvalidArgumentException $e) {
            return FiscalConnectionResult::fail('configuration_missing', $e->getMessage());
        }

        return FiscalConnectionResult::ok('Credenciales SOL y certificado PEM completo presentes para RUC ' . $ruc);
    }

    public function emit(
        FiscalDocument $doc,
        Empresa $empresa,
        string $documentClass,
        DocumentInterface $greenterDoc
    ): FiscalEmitResult {
        if ($documentClass === Despatch::class) {
            throw new \RuntimeException('GRE debe emitirse vía GreRestSunatProvider, no SOAP');
        }

        $ruc = trim((string) $greenterDoc->getCompany()->getRuc());
        $see = $this->seeFactory->build($documentClass, $ruc);
        $result = $see->send($greenterDoc);
        $signedXml = $see->getFactory()->getLastXml();

        $out = new FiscalEmitResult();
        $out->signedXml = $signedXml;
        $out->hash = $this->extractHash($signedXml);
        if (method_exists($result, 'getTicket') && $result->getTicket()) {
            $out->ticket = (string) $result->getTicket();
        }
        if (method_exists($result, 'getCdrZip') && $result->getCdrZip()) {
            $out->cdrZip = $result->getCdrZip();
        }

        $cdr = $this->extractCdrResponse($result);
        if ($cdr !== null) {
            $classified = SunatCdrClassifier::fromCdrResponse($cdr);
            $out->sunatCode = $classified['code'];
            $out->sunatMessage = $classified['message'];
            $out->cdrNotes = $classified['notes'];
            $out->success = $classified['success'];
            $out->rejected = $classified['rejected'];
            $out->observed = $classified['observed'];
        } else {
            $out->sunatCode = $this->extractSunatCodeWithoutCdr($result);
            $out->sunatMessage = $this->extractSunatMessageWithoutCdr($result);
            $out->success = false;
            $out->rejected = false;
            $out->observed = false;
            if ($out->cdrZip !== null && $out->cdrZip !== '') {
                $out->sunatMessage = ($out->sunatMessage ?? '') !== ''
                    ? $out->sunatMessage
                    : 'CDR recibido sin detalle parseable';
            }
        }

        $out->sunatResponse = ['raw' => json_decode(json_encode($result), true)];

        return $out;
    }

    private function extractHash(string $xml): string
    {
        if ($xml === '') {
            return '';
        }
        try {
            return (new XmlUtils())->getHashSign($xml);
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function extractCdrResponse(object $result): ?CdrResponse
    {
        if (method_exists($result, 'getCdrResponse') && $result->getCdrResponse() instanceof CdrResponse) {
            return $result->getCdrResponse();
        }

        return null;
    }

    private function extractSunatCodeWithoutCdr(object $result): ?string
    {
        if (method_exists($result, 'getError') && $result->getError()) {
            return 'error';
        }
        return null;
    }

    private function extractSunatMessageWithoutCdr(object $result): ?string
    {
        if (method_exists($result, 'getError') && $result->getError()) {
            $err = $result->getError();
            if (method_exists($err, 'getMessage')) {
                return (string) $err->getMessage();
            }
        }
        return null;
    }
}
