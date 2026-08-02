<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Provider;

use App\Entity\Empresa;
use App\Entity\FiscalDocument;
use App\Service\Fiscal\FiscalDocumentClassResolver;
use App\Service\Fiscal\PemCertificateValidator;
use App\Service\Gre\GreOAuthCredentialResolver;
use App\Service\ConfigProviderInterface;
use App\Service\SeeApiFactory;
use Greenter\Model\Despatch\Despatch;
use Greenter\Model\DocumentInterface;
use Greenter\Model\Response\CdrResponse;
use Greenter\Report\XmlUtils;
use Greenter\Sunat\GRE\ApiException;

/**
 * GRE Remitente (09) y Transportista (31) vía REST OAuth (Greenter\Api).
 * No utiliza SOAP. Aislado del SunatDirectProvider.
 */
class GreRestSunatProvider extends AbstractFiscalProvider
{
    private SeeApiFactory $seeApiFactory;
    private ConfigProviderInterface $config;
    private string $dataPath;

    public function __construct(
        SeeApiFactory $seeApiFactory,
        ConfigProviderInterface $config,
        string $dataPath
    ) {
        $this->seeApiFactory = $seeApiFactory;
        $this->config = $config;
        $this->dataPath = $dataPath;
    }

    public function getName(): string
    {
        return 'gre_rest_sunat';
    }

    public function supports(FiscalDocument $doc, Empresa $empresa): bool
    {
        $mode = strtolower(trim((string) ($doc->getSendMode() ?? $empresa->getSendMode())));
        if ($mode !== '' && $mode !== 'sunat_direct' && $mode !== 'sunat') {
            return false;
        }

        $tipo = strtoupper(trim((string) $doc->getDocumentType()));
        if ($tipo === '') {
            return true;
        }

        return in_array($tipo, ['09', '31'], true);
    }

    public function validateConnection(Empresa $empresa): FiscalConnectionResult
    {
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

        if (!GreOAuthCredentialResolver::isConfigured($empresa, $this->config)) {
            return FiscalConnectionResult::fail(
                'configuration_missing',
                'OAuth GRE no configurado (gre_client_id/secret o fallback Nubefact Test en pruebas)'
            );
        }

        try {
            $api = $this->seeApiFactory->buildForEmpresa($empresa);
            $reflection = new \ReflectionClass($api);
            $method = $reflection->getMethod('createSender');
            $method->setAccessible(true);
            $method->invoke($api);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if ($e instanceof ApiException || str_contains(strtolower($msg), '401') || str_contains(strtolower($msg), 'oauth')) {
                return FiscalConnectionResult::fail('invalid_credentials', 'OAuth GRE rechazado: ' . $msg);
            }
        }

        return FiscalConnectionResult::ok('GRE REST OAuth operativo para RUC ' . $empresa->getRuc());
    }

    public function emit(
        FiscalDocument $doc,
        Empresa $empresa,
        string $documentClass,
        DocumentInterface $greenterDoc
    ): FiscalEmitResult {
        if ($documentClass !== Despatch::class) {
            throw new \RuntimeException('GreRestSunatProvider solo soporta Despatch (GRE 09/31)');
        }

        $ruc = trim((string) $greenterDoc->getCompany()->getRuc());
        $api = $this->seeApiFactory->build($ruc);
        $result = $api->send($greenterDoc);
        $signedXml = (string) ($api->getLastXml() ?? '');

        $out = new FiscalEmitResult();
        $out->signedXml = $signedXml;
        $out->hash = $this->extractHash($signedXml);

        if ($result !== null && method_exists($result, 'getTicket') && $result->getTicket()) {
            $out->ticket = (string) $result->getTicket();
        }

        if ($result !== null && method_exists($result, 'getCdrZip') && $result->getCdrZip()) {
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
        } elseif ($out->ticket !== null && $out->ticket !== '' && FiscalDocumentClassResolver::isTicketBased($documentClass)) {
            $out->success = false;
            $out->rejected = false;
            $out->observed = false;
            $out->sunatMessage = 'GRE enviada; pendiente consulta de ticket SUNAT';
            $out->pendingTicketPoll = true;
        } elseif ($result !== null && method_exists($result, 'isSuccess') && $result->isSuccess()) {
            $out->success = true;
        } elseif ($result !== null && method_exists($result, 'getError') && $result->getError()) {
            $err = $result->getError();
            // El código real de SUNAT, no el literal 'error': sin él, en la base y en
            // el panel un rechazo de credenciales se ve igual que un XML inválido.
            $greCode = method_exists($err, 'getCode') ? trim((string) $err->getCode()) : '';
            $out->sunatCode = $greCode !== '' ? $greCode : 'error';
            $out->sunatMessage = method_exists($err, 'getMessage') ? (string) $err->getMessage() : 'Error GRE REST';
            $out->rejected = true;
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

    private function extractCdrResponse(?object $result): ?CdrResponse
    {
        if ($result === null) {
            return null;
        }
        if (method_exists($result, 'getCdrResponse') && $result->getCdrResponse() instanceof CdrResponse) {
            return $result->getCdrResponse();
        }

        return null;
    }
}
