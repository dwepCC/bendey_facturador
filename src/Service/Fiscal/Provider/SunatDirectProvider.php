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
use Greenter\Ws\Services\ConsultCdrService;
use Greenter\Ws\Services\SoapClient;
use Greenter\Ws\Services\SunatEndpoints;

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

        // Hasta acá solo se comprobó que los campos están LLENOS, que no es lo mismo
        // que que funcionen. Un usuario SOL equivocado pasaba esta prueba con un OK
        // verde y el problema recién aparecía al emitir, con comprobantes reales.
        // Ahora se pregunta a SUNAT.
        if (strtolower(trim($empresa->getAmbiente())) !== 'produccion') {
            return FiscalConnectionResult::ok(
                'Credenciales y certificado presentes para RUC ' . $ruc .
                '. En ambiente de pruebas no se valida contra SUNAT.'
            );
        }

        return $this->probeSunatCredentials($empresa, $ruc);
    }

    /**
     * Autentica contra SUNAT consultando el CDR de un comprobante inexistente.
     *
     * Es una CONSULTA: no emite nada ni deja rastro fiscal, pero viaja con el usuario
     * y la clave SOL, así que SUNAT responde igual que en un envío si las credenciales
     * están mal. Que el comprobante no exista es indiferente — lo que se mide es si la
     * autenticación pasó.
     */
    private function probeSunatCredentials(Empresa $empresa, string $ruc): FiscalConnectionResult
    {
        try {
            $ws = new SoapClient(SunatEndpoints::FE_CONSULTA_CDR . '?wsdl');
            $ws->setCredentials(trim($empresa->getSolUser()), trim($empresa->getSolPass()));
            $service = new ConsultCdrService();
            $service->setClient($ws);

            // Serie y número deliberadamente imposibles: solo interesa el resultado de
            // la autenticación, no el del documento.
            $result = $service->getStatusCdr($ruc, '01', 'F999', '99999999');
        } catch (\Throwable $e) {
            return FiscalConnectionResult::fail('error', 'No se pudo contactar a SUNAT: ' . $e->getMessage());
        }

        if ($result->isSuccess()) {
            return FiscalConnectionResult::ok('SUNAT aceptó las credenciales del RUC ' . $ruc);
        }

        $error = $result->getError();
        $code = $error !== null ? trim((string) $error->getCode()) : '';
        $message = $error !== null ? trim((string) $error->getMessage()) : '';

        if (self::isCredentialError($code, $message)) {
            return FiscalConnectionResult::fail(
                'invalid_credentials',
                $message !== '' ? $message : 'SUNAT rechazó el usuario o la clave SOL'
            );
        }

        // Cualquier otro error (típicamente "la constancia no existe") significa que la
        // autenticación SÍ pasó: SUNAT llegó a evaluar el documento.
        return FiscalConnectionResult::ok('SUNAT aceptó las credenciales del RUC ' . $ruc);
    }

    /**
     * Distingue un rechazo de credenciales de cualquier otro error de SUNAT. Se mira
     * el código y también el texto, porque SUNAT no siempre devuelve el código en la
     * consulta de CDR.
     */
    private static function isCredentialError(string $code, string $message): bool
    {
        if (in_array($code, ['0102', '0103', '0105', '0109', '0110', '0111'], true)) {
            return true;
        }
        $normalized = mb_strtolower($message);
        foreach (['usuario', 'clave', 'contraseña', 'no existe el usuario', 'autenticac'] as $needle) {
            if ($normalized !== '' && str_contains($normalized, $needle)) {
                return true;
            }
        }
        return false;
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

    /**
     * Código de SUNAT cuando el envío falló antes de producir un CDR.
     *
     * Devuelve el código REAL del fault (0103, 0102, 2335…), no el literal 'error'
     * que se guardaba antes: con "error" en la columna, un rechazo de credenciales y
     * un XML mal formado se veían idénticos en la base y en el panel, y había que
     * leer el mensaje a mano para saber qué pasó. Solo si SUNAT no manda código se
     * cae a 'error'.
     */
    private function extractSunatCodeWithoutCdr(object $result): ?string
    {
        if (!method_exists($result, 'getError') || !$result->getError()) {
            return null;
        }
        $err = $result->getError();
        $code = method_exists($err, 'getCode') ? trim((string) $err->getCode()) : '';

        return $code !== '' ? $code : 'error';
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
