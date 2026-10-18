<?php

declare(strict_types=1);

namespace App\Tests\Service\Fiscal;

use App\Entity\Empresa;
use App\Entity\FiscalDocument;
use App\Repository\EmpresaRepository;
use App\Service\Fiscal\FiscalQueueService;
use App\Service\Fiscal\FiscalStorageService;
use App\Service\Fiscal\FiscalWebhookService;
use App\Service\Fiscal\Verification\FiscalSunatVerifier;
use App\Service\Fiscal\Verification\SunatConsultClientInterface;
use App\Service\Fiscal\Verification\SunatValidezApiClientInterface;
use App\Service\Fiscal\Verification\ValidaPseConsultClientInterface;
use App\Service\Fiscal\Verification\VerificationApplyException;
use App\Service\Fiscal\Verification\VerificationResult;
use Doctrine\ORM\EntityManagerInterface;
use Greenter\Model\Response\CdrResponse;
use Greenter\Model\Response\Error;
use Greenter\Model\Response\StatusCdrResult;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * «Verificar en SUNAT» del panel del facturador, con las respuestas reales del 2026-10-18:
 * - billConsultService con clave SOL: boleta B001-167 y factura FF01-9945 → 0004 + CDR código 0;
 *   caldosoaqp B001-10890 → CDR código 0 con observación 4439.
 * - Empresas PSE con clave SOL de relleno «PSE»: HTTP Unauthorized.
 * - ValidaPSE GET /api/cpe/consultar sobre una boleta y una factura aceptadas: HTTP 202 +
 *   {"isSuccess":false,"estado":501,"code":"HTTP","errores":"Internal Server Error"}.
 */
class FiscalSunatVerifierTest extends TestCase
{
    private const RUC = '20607046736';

    public function testCdrAceptadoEsAceptado(): void
    {
        $v = FiscalSunatVerifier::fromSunatCdrResult($this->cdrResult('0', 'La Factura numero F001-2, ha sido aceptada'));

        $this->assertSame(VerificationResult::ACCEPTED, $v->outcome);
        $this->assertSame('0004', $v->statusCode);
        $this->assertTrue($v->isApplicable());
        $this->assertSame(FiscalDocument::STATUS_ACCEPTED, $v->proposedStatus());
        $this->assertTrue($v->toArray()['has_cdr']);
    }

    public function testCdrConObservacionEsObservado(): void
    {
        $v = FiscalSunatVerifier::fromSunatCdrResult($this->cdrResult(
            '0',
            'La Boleta de Venta numero B001-10890, ha sido aceptado con observaciones',
            ['4439 - El emisor no se encuentra en el Padrón de la tasa especial del IGV']
        ));

        $this->assertSame(VerificationResult::OBSERVED, $v->outcome);
        $this->assertSame(FiscalDocument::STATUS_OBSERVED, $v->proposedStatus());
    }

    public function testCdrDeRechazoEsRechazado(): void
    {
        $v = FiscalSunatVerifier::fromSunatCdrResult($this->cdrResult('2108', 'Presentacion fuera de fecha'));

        $this->assertSame(VerificationResult::REJECTED, $v->outcome);
        $this->assertSame('2108', $v->cdrCode);
    }

    public function testUnauthorizedNoConcluyeYExplicaElUsuarioSol(): void
    {
        $v = FiscalSunatVerifier::fromSunatCdrResult($this->errorResult('HTTP', 'Unauthorized'));

        $this->assertSame(VerificationResult::UNAVAILABLE, $v->outcome);
        $this->assertFalse($v->isApplicable());
        $this->assertStringContainsString('usuario SOL', (string) $v->reason);
    }

    /**
     * @dataProvider estados
     */
    public function testConsultaDeEstadoSinCdr(string $code, string $esperado): void
    {
        $r = (new StatusCdrResult())->setSuccess(true)->setCode($code)->setMessage('x');
        $this->assertSame($esperado, FiscalSunatVerifier::fromSunatStatusResult($r)->outcome);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function estados(): array
    {
        return [
            '0001 existe y aceptado' => ['0001', VerificationResult::ACCEPTED],
            '0002 rechazado' => ['0002', VerificationResult::REJECTED],
            '0003 de baja' => ['0003', VerificationResult::VOIDED],
            '0011 no existe' => ['0011', VerificationResult::NOT_FOUND],
            '0012 no le pertenece' => ['0012', VerificationResult::UNAVAILABLE],
        ];
    }

    public function testValidaPseConsultarRealNoDaVeredicto(): void
    {
        $v = FiscalSunatVerifier::fromValidaPseResponse(
            202,
            '{"isSuccess":false,"estado":501,"code":"HTTP","errores":"Internal Server Error"}',
            '20614224682-03-B003-896'
        );

        $this->assertSame(VerificationResult::UNAVAILABLE, $v->outcome);
        $this->assertSame(VerificationResult::SOURCE_VALIDAPSE, $v->source);
        $this->assertStringContainsString('Internal Server Error', (string) $v->reason);
    }

    public function testValidaPseConCdrSeClasifica(): void
    {
        $cdrXml = '<?xml version="1.0" encoding="UTF-8"?><ar:ApplicationResponse xmlns:ar="urn:oasis:names:specification:ubl:schema:xsd:ApplicationResponse-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"><cbc:ID>1</cbc:ID><cac:DocumentResponse><cac:Response><cbc:ReferenceID>B003-896</cbc:ReferenceID><cbc:ResponseCode>0</cbc:ResponseCode><cbc:Description>La Boleta numero B003-896, ha sido aceptada</cbc:Description></cac:Response></cac:DocumentResponse></ar:ApplicationResponse>';
        $v = FiscalSunatVerifier::fromValidaPseResponse(
            200,
            json_encode(['isSuccess' => true, 'estado' => 200, 'code' => '0', 'cdr' => base64_encode($cdrXml)]),
            '20614224682-03-B003-896'
        );

        $this->assertSame(VerificationResult::ACCEPTED, $v->outcome);
        $this->assertSame(VerificationResult::SOURCE_VALIDAPSE, $v->source);
        $this->assertNotNull($v->cdrZip);
        $this->assertStringStartsWith('PK', (string) $v->cdrZip);
    }

    public function testClaveSolDeRellenoNoSirve(): void
    {
        $this->assertFalse(FiscalSunatVerifier::hasUsableSolCredentials($this->empresa('PSE', 'PSE')));
        $this->assertFalse(FiscalSunatVerifier::hasUsableSolCredentials($this->empresa('', '')));
        $this->assertTrue(FiscalSunatVerifier::hasUsableSolCredentials($this->empresa(self::RUC . 'MODDATOS', 'secreto')));
    }

    public function testEmpresaPseSinSolUsaValidaPse(): void
    {
        $empresa = $this->empresa('PSE', 'PSE', 'pse');
        $sunat = $this->createMock(SunatConsultClientInterface::class);
        $sunat->expects($this->never())->method('getStatusCdr');
        $pse = $this->createMock(ValidaPseConsultClientInterface::class);
        $pse->expects($this->once())->method('consultar')
            ->with('https://app.validapse.com', 'tok', self::RUC . '-01-F001-2')
            ->willReturn(['http' => 202, 'body' => '{"isSuccess":false,"estado":501,"code":"HTTP","errores":"Internal Server Error"}']);

        $v = $this->verifier($empresa, $sunat, $pse)->verify($this->doc(FiscalDocument::STATUS_REJECTED));

        $this->assertSame(VerificationResult::UNAVAILABLE, $v->outcome);
    }

    public function testSinCdrPreguntaElEstado(): void
    {
        $sunat = $this->createMock(SunatConsultClientInterface::class);
        $sunat->method('getStatusCdr')->willReturn($this->errorResult('0125', 'No se pudo obtener la constancia'));
        $sunat->expects($this->once())->method('getStatus')
            ->willReturn((new StatusCdrResult())->setSuccess(true)->setCode('0011')->setMessage('El comprobante de pago electrónico no existe.'));

        $v = $this->verifier($this->empresa(self::RUC . 'MODDATOS', 'x'), $sunat)->verify($this->doc(FiscalDocument::STATUS_ERROR));

        $this->assertSame(VerificationResult::NOT_FOUND, $v->outcome);
        $this->assertFalse($v->isApplicable());
    }

    public function testEmpresaPseSiValidaPseFallaCaeASunatConUsuarioSol(): void
    {
        $sunat = $this->createMock(SunatConsultClientInterface::class);
        $sunat->expects($this->once())->method('getStatusCdr')
            ->with(self::RUC . 'CONSULTA1', 'clave', self::RUC, '01', 'F001', 2)
            ->willReturn($this->cdrResult('0', 'La Boleta numero B003-895, ha sido aceptada'));
        $pse = $this->createMock(ValidaPseConsultClientInterface::class);
        $pse->expects($this->once())->method('consultar')
            ->willReturn(['http' => 202, 'body' => '{"isSuccess":false,"estado":501,"code":"HTTP","errores":"Internal Server Error"}']);

        $v = $this->verifier($this->empresa(self::RUC . 'CONSULTA1', 'clave', 'pse'), $sunat, $pse)->verify($this->doc(FiscalDocument::STATUS_REJECTED));

        $this->assertSame(VerificationResult::ACCEPTED, $v->outcome);
        $this->assertSame(VerificationResult::SOURCE_SUNAT_CDR, $v->source);
    }

    public function testEnPseValidaPseVaPrimeroYSiRespondeNoSeConsultaSunat(): void
    {
        $cdrXml = '<?xml version="1.0" encoding="UTF-8"?><ar:ApplicationResponse xmlns:ar="urn:oasis:names:specification:ubl:schema:xsd:ApplicationResponse-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"><cbc:ID>1</cbc:ID><cac:DocumentResponse><cac:Response><cbc:ReferenceID>F001-2</cbc:ReferenceID><cbc:ResponseCode>0</cbc:ResponseCode><cbc:Description>La Factura numero F001-2, ha sido aceptada</cbc:Description></cac:Response></cac:DocumentResponse></ar:ApplicationResponse>';
        $pse = $this->createMock(ValidaPseConsultClientInterface::class);
        $pse->method('consultar')->willReturn(['http' => 200, 'body' => (string) json_encode(['isSuccess' => true, 'code' => '0', 'cdr' => base64_encode($cdrXml)])]);
        $sunat = $this->createMock(SunatConsultClientInterface::class);
        $sunat->expects($this->never())->method('getStatusCdr');

        $v = $this->verifier($this->empresa(self::RUC . 'CONSULTA1', 'clave', 'pse'), $sunat, $pse)->verify($this->doc(FiscalDocument::STATUS_REJECTED));

        $this->assertSame(VerificationResult::ACCEPTED, $v->outcome);
        $this->assertSame(VerificationResult::SOURCE_VALIDAPSE, $v->source);
    }

    public function testSinSolUsaLaApiDeValidezComoRespaldo(): void
    {
        $empresa = $this->empresa('PSE', 'PSE', 'pse');
        $empresa->setCpeApiClientId('cpe-id');
        $empresa->setCpeApiClientSecret('cpe-secret');
        $validez = $this->createMock(SunatValidezApiClientInterface::class);
        $validez->expects($this->once())->method('validar')
            ->with('cpe-id', 'cpe-secret', self::RUC, [
                'numRuc' => self::RUC, 'codComp' => '01', 'numeroSerie' => 'F001', 'numero' => 2,
                'fechaEmision' => '18/10/2026', 'monto' => 75.0,
            ])
            ->willReturn(['http' => 200, 'body' => '{"success":true,"message":"Operation Success! ","data":{"estadoCp":"1","estadoRuc":"00","condDomiRuc":"00"}}']);
        $pse = $this->createMock(ValidaPseConsultClientInterface::class);
        $pse->method('consultar')->willReturn(['http' => 0, 'body' => '']);

        $v = $this->verifier($empresa, $this->createMock(SunatConsultClientInterface::class), $pse, null, null, $validez)
            ->verify($this->doc(FiscalDocument::STATUS_REJECTED, ['fechaEmision' => '2026-10-18T10:09:46-05:00', 'mtoImpVenta' => 75]));

        $this->assertSame(VerificationResult::ACCEPTED, $v->outcome);
        $this->assertSame(VerificationResult::SOURCE_SUNAT_VALIDEZ, $v->source);
        $this->assertFalse($v->toArray()['has_cdr']);
    }

    public function testSolFallaYCaeALaApiDeValidez(): void
    {
        $empresa = $this->empresa(self::RUC . 'CONSULTA1', 'mala', 'pse');
        $empresa->setCpeApiClientId('cpe-id');
        $empresa->setCpeApiClientSecret('cpe-secret');
        $sunat = $this->createMock(SunatConsultClientInterface::class);
        $sunat->method('getStatusCdr')->willReturn($this->errorResult('HTTP', 'Unauthorized'));
        $validez = $this->createMock(SunatValidezApiClientInterface::class);
        $validez->method('validar')->willReturn(['http' => 200, 'body' => '{"success":true,"data":{"estadoCp":"0"}}']);

        $v = $this->verifier($empresa, $sunat, null, null, null, $validez)
            ->verify($this->doc(FiscalDocument::STATUS_REJECTED, ['fechaEmision' => '2026-10-18T10:09:46-05:00', 'mtoImpVenta' => 75]));

        $this->assertSame(VerificationResult::NOT_FOUND, $v->outcome);
    }

    public function testSinNingunaCredencialLoDice(): void
    {
        $v = $this->verifier($this->empresa('PSE', 'PSE'), $this->createMock(SunatConsultClientInterface::class))
            ->verify($this->doc(FiscalDocument::STATUS_REJECTED));

        $this->assertSame(VerificationResult::UNAVAILABLE, $v->outcome);
        $this->assertStringContainsString('Panel Central', (string) $v->reason);
    }

    /**
     * @dataProvider estadosValidez
     */
    public function testRespuestasDeLaApiDeValidez(string $body, string $esperado): void
    {
        $this->assertSame($esperado, FiscalSunatVerifier::fromValidezApiResponse(200, $body)->outcome);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function estadosValidez(): array
    {
        return [
            'aceptado' => ['{"success":true,"data":{"estadoCp":"1"}}', VerificationResult::ACCEPTED],
            'anulado' => ['{"success":true,"data":{"estadoCp":"2"}}', VerificationResult::VOIDED],
            'no existe' => ['{"success":true,"data":{"estadoCp":"0"}}', VerificationResult::NOT_FOUND],
            'no autorizado' => ['{"success":true,"data":{"estadoCp":"4"}}', VerificationResult::UNAVAILABLE],
            'error de la api' => ['{"success":false,"message":"El token no es valido","errorCode":"0103"}', VerificationResult::UNAVAILABLE],
            'no json' => ['<html>', VerificationResult::UNAVAILABLE],
        ];
    }

    public function testEmpresaEnPruebasNoConsulta(): void
    {
        $empresa = $this->empresa(self::RUC . 'MODDATOS', 'x');
        $empresa->setAmbiente('pruebas');
        $sunat = $this->createMock(SunatConsultClientInterface::class);
        $sunat->expects($this->never())->method('getStatusCdr');

        $v = $this->verifier($empresa, $sunat)->verify($this->doc(FiscalDocument::STATUS_REJECTED));

        $this->assertSame(VerificationResult::UNAVAILABLE, $v->outcome);
    }

    public function testAplicarAceptadoGuardaEvidenciaCdrYAvisaAlTenant(): void
    {
        $doc = $this->doc(FiscalDocument::STATUS_REJECTED);
        $doc->setSunatCode('501');
        $doc->setSunatMessage('1033 - El comprobante fue registrado previamente con otros datos');
        $doc->setRejectedAt(new \DateTimeImmutable('-1 day'));

        $sunat = $this->createMock(SunatConsultClientInterface::class);
        $sunat->method('getStatusCdr')->willReturn($this->cdrResult('0', 'La Factura numero F001-2, ha sido aceptada'));
        $storage = $this->createMock(FiscalStorageService::class);
        $storage->expects($this->once())->method('storeCdr')
            ->with('negociacionescostaazulsac', '01', 'F001', '2', 'PKzip')
            ->willReturn('http://x/cdr.zip');
        $webhook = $this->createMock(FiscalWebhookService::class);
        $webhook->expects($this->once())->method('notifyStatus')->with($doc);

        $result = $this->verifier($this->empresa(self::RUC . 'MODDATOS', 'x'), $sunat, null, $storage, $webhook)
            ->apply($doc, VerificationResult::ACCEPTED, 'operador1');

        $this->assertSame(VerificationResult::ACCEPTED, $result->outcome);
        $this->assertSame(FiscalDocument::STATUS_ACCEPTED, $doc->getStatus());
        $this->assertSame('0', $doc->getSunatCode());
        $this->assertNull($doc->getRejectedAt());
        $this->assertNotNull($doc->getAcceptedAt());
        $this->assertSame('http://x/cdr.zip', $doc->getCdrUrl());
        $this->assertSame('operador1', $doc->getVerifiedBy());
        $this->assertSame(VerificationResult::SOURCE_SUNAT_CDR, $doc->getVerificationSource());
        $evidence = json_decode((string) $doc->getVerificationJson(), true);
        $this->assertSame('rejected', $evidence['previous']['status']);
        $this->assertSame('501', $evidence['previous']['sunat_code']);
        $this->assertSame('operador1', $evidence['applied_by']);
        $this->assertStringContainsString('verificado en SUNAT', (string) $doc->getSunatMessage());
    }

    /**
     * Prueba real del 2026-10-18 con B003-895/905 de comercializadoradecalzadosadri: getStatusCdr
     * → 0125 «No se pudo obtener la constancia»; getStatus → 0001 «existe y está aceptado».
     */
    public function testSinConstanciaPeroExisteSeAplicaComoAceptadoSinCdr(): void
    {
        $doc = $this->doc(FiscalDocument::STATUS_REJECTED);
        $doc->setSunatCode('501');
        $sunat = $this->createMock(SunatConsultClientInterface::class);
        $sunat->method('getStatusCdr')->willReturn($this->errorResult('0125', 'No se pudo obtener la constancia'));
        $sunat->method('getStatus')->willReturn((new StatusCdrResult())->setSuccess(true)->setCode('0001')->setMessage('El comprobante existe y está aceptado.'));
        $storage = $this->createMock(FiscalStorageService::class);
        $storage->expects($this->never())->method('storeCdr');
        $webhook = $this->createMock(FiscalWebhookService::class);
        $webhook->expects($this->once())->method('notifyStatus');

        $verifier = $this->verifier($this->empresa('BND52148', 'clave'), $sunat, null, $storage, $webhook);
        $preview = $verifier->verify($doc);
        $this->assertSame(VerificationResult::ACCEPTED, $preview->outcome);
        $this->assertTrue($preview->isApplicable());
        $this->assertStringContainsString('sin CDR', $preview->label());

        $verifier->apply($doc, VerificationResult::ACCEPTED, 'op');
        $this->assertSame(FiscalDocument::STATUS_ACCEPTED, $doc->getStatus());
        $this->assertSame('0', $doc->getSunatCode());
        $this->assertNull($doc->getCdrUrl());
        $this->assertSame(VerificationResult::SOURCE_SUNAT_STATUS, $doc->getVerificationSource());
        $this->assertStringContainsString('sin CDR', (string) $doc->getSunatMessage());
    }

    public function testUsuarioSecundarioSinRucSeUsaConElRucDelante(): void
    {
        $sunat = $this->createMock(SunatConsultClientInterface::class);
        $sunat->expects($this->once())->method('getStatusCdr')
            ->with(self::RUC . 'BND52148', 'clave')
            ->willReturn($this->cdrResult('0', 'aceptada'));

        $empresa = $this->empresa('BND52148', 'clave', 'pse');
        $this->assertTrue(FiscalSunatVerifier::hasUsableSolCredentials($empresa));
        $pse = $this->createMock(ValidaPseConsultClientInterface::class);
        $pse->method('consultar')->willReturn(['http' => 202, 'body' => '{"isSuccess":false,"code":"HTTP","errores":"Internal Server Error"}']);

        $v = $this->verifier($empresa, $sunat, $pse)->verify($this->doc(FiscalDocument::STATUS_REJECTED));
        $this->assertSame(VerificationResult::ACCEPTED, $v->outcome);
    }

    public function testNormalizacionDelUsuarioSol(): void
    {
        $this->assertSame(self::RUC . 'BND52148', Empresa::normalizeSolUser('BND52148', self::RUC));
        $this->assertSame(self::RUC . 'BND52148', Empresa::normalizeSolUser(' ' . self::RUC . 'BND52148 ', self::RUC));
        $this->assertSame(self::RUC . 'MODDATOS', Empresa::normalizeSolUser(self::RUC . 'MODDATOS', self::RUC));
        $this->assertSame('PSE', Empresa::normalizeSolUser('PSE', self::RUC));
        $this->assertSame('', Empresa::normalizeSolUser('', self::RUC));
        $this->assertSame('BND52148', Empresa::normalizeSolUser('BND52148', ''));
        $this->assertFalse(FiscalSunatVerifier::hasUsableSolCredentials($this->empresa('PSE', 'PSE')));
    }

    public function testNoSeAplicaSiSunatRespondeDistinto(): void
    {
        $doc = $this->doc(FiscalDocument::STATUS_REJECTED);
        $sunat = $this->createMock(SunatConsultClientInterface::class);
        $sunat->method('getStatusCdr')->willReturn($this->cdrResult('2108', 'Presentacion fuera de fecha'));
        $webhook = $this->createMock(FiscalWebhookService::class);
        $webhook->expects($this->never())->method('notifyStatus');

        $this->expectException(VerificationApplyException::class);
        try {
            $this->verifier($this->empresa(self::RUC . 'MODDATOS', 'x'), $sunat, null, null, $webhook)
                ->apply($doc, VerificationResult::ACCEPTED, 'op');
        } finally {
            $this->assertSame(FiscalDocument::STATUS_REJECTED, $doc->getStatus());
        }
    }

    public function testNoSeAplicaSobreUnAceptado(): void
    {
        $sunat = $this->createMock(SunatConsultClientInterface::class);
        $sunat->expects($this->never())->method('getStatusCdr');

        $this->expectException(VerificationApplyException::class);
        $this->verifier($this->empresa(self::RUC . 'MODDATOS', 'x'), $sunat)
            ->apply($this->doc(FiscalDocument::STATUS_ACCEPTED), VerificationResult::ACCEPTED, 'op');
    }

    public function testNoExisteNoSeAplica(): void
    {
        $sunat = $this->createMock(SunatConsultClientInterface::class);
        $sunat->method('getStatusCdr')->willReturn($this->errorResult('0127', 'El ticket no existe'));
        $sunat->method('getStatus')->willReturn((new StatusCdrResult())->setSuccess(true)->setCode('0011')->setMessage('no existe'));

        $this->expectException(VerificationApplyException::class);
        $this->verifier($this->empresa(self::RUC . 'MODDATOS', 'x'), $sunat)
            ->apply($this->doc(FiscalDocument::STATUS_ERROR), VerificationResult::NOT_FOUND, 'op');
    }

    private function verifier(
        Empresa $empresa,
        SunatConsultClientInterface $sunat,
        ?ValidaPseConsultClientInterface $pse = null,
        ?FiscalStorageService $storage = null,
        ?FiscalWebhookService $webhook = null,
        ?SunatValidezApiClientInterface $validez = null,
    ): FiscalSunatVerifier {
        $repo = $this->createMock(EmpresaRepository::class);
        $repo->method('find')->willReturn($empresa);

        return new FiscalSunatVerifier(
            $repo,
            $sunat,
            $pse ?? $this->createMock(ValidaPseConsultClientInterface::class),
            $this->createMock(EntityManagerInterface::class),
            $storage ?? $this->createMock(FiscalStorageService::class),
            $webhook ?? $this->createMock(FiscalWebhookService::class),
            $this->createMock(FiscalQueueService::class),
            new NullLogger(),
            null,
            $validez
        );
    }

    /**
     * @param array<int, string> $notes
     */
    private function cdrResult(string $code, string $description, array $notes = []): StatusCdrResult
    {
        $cdr = (new CdrResponse())->setCode($code)->setDescription($description)->setNotes($notes);

        return (new StatusCdrResult())->setSuccess(true)->setCode('0004')->setMessage('La constancia existe')
            ->setCdrZip('PKzip')->setCdrResponse($cdr);
    }

    private function errorResult(string $code, string $message): StatusCdrResult
    {
        return (new StatusCdrResult())->setSuccess(false)->setError((new Error())->setCode($code)->setMessage($message));
    }

    private function empresa(string $solUser, string $solPass, string $sendMode = 'sunat_direct'): Empresa
    {
        $e = new Empresa();
        $e->setRuc(self::RUC);
        $e->setSolUser($solUser);
        $e->setSolPass($solPass);
        $e->setAmbiente('produccion');
        $e->setSendMode($sendMode);
        $e->setPseBaseUrl('https://app.validapse.com');
        $e->setPseToken('tok');

        return $e;
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function doc(string $status, array $extra = []): FiscalDocument
    {
        $doc = new FiscalDocument();
        $doc->setDocumentUuid('0c6f66c6-a8fa-4bc0-8365-0d22ab8f557e');
        $doc->setTenantId(1);
        $doc->setTenantSlug('negociacionescostaazulsac');
        $doc->setSaleId(5);
        $doc->setDocumentType('01');
        $doc->setSeries('F001');
        $doc->setNumber('2');
        $doc->setSnapshotJson(json_encode(['company' => ['ruc' => self::RUC], 'serie' => 'F001', 'correlativo' => '2'] + $extra, JSON_THROW_ON_ERROR));
        $doc->setStatus($status);

        return $doc;
    }
}
