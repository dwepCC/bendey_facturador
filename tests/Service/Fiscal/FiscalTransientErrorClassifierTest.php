<?php

declare(strict_types=1);

namespace App\Tests\Service\Fiscal;

use App\Greenter\Factory\XmlBuilderResolverFactory;
use App\Service\Fiscal\Provider\FiscalTransientErrorClassifier;
use App\Service\Fiscal\Provider\PseAuthBuilder;
use App\Service\Fiscal\Provider\ValidaPseProvider;
use PHPUnit\Framework\TestCase;

/**
 * Caso real 2026-10-18 (comercializadoradecalzadosadri): B003-894 y B003-895 quedaron `rejected`
 * por respuestas de indisponibilidad de SUNAT/ValidaPSE, y backend_go obligó a re-emitirlas con
 * número nuevo. Las respuestas de abajo son las que guardó producción en pse_response_json.
 */
class FiscalTransientErrorClassifierTest extends TestCase
{
    private const RESP_0109 = '{"isSuccess":false,"estado":501,"code":"0109","errores":"El sistema no puede responder su solicitud. (El servicio de autenticación no está disponible)"}';
    private const RESP_SERVER_ERROR = '{"message":"Server Error"}';
    private const RESP_HTTP_BAD_REQUEST = '{"isSuccess":false,"estado":501,"code":"HTTP","errores":"Bad Request","xml":""}';
    // Rechazos reales de ValidaPSE en producción: mismo estado 501, pero con código SUNAT.
    private const RESP_1033 = '{"isSuccess":false,"estado":501,"code":"1033","errores":"1033 - El comprobante fue registrado previamente con otros datos - Detalle: value=\'ticket: 123\'"}';
    private const RESP_3462 = '{"isSuccess":false,"estado":501,"code":"3462","errores":"La tasa del IGV debe ser la misma en todas las líneas o ítems del documento"}';
    private const RESP_0102 = '{"isSuccess":false,"estado":501,"code":"0102","errores":"Usuario o contraseña incorrectos"}';

    /**
     * @return array<string, array{int, string}>
     */
    public static function transitorias(): array
    {
        return [
            '0109 con HTTP 200' => [200, self::RESP_0109],
            '0109 con HTTP 501' => [501, self::RESP_0109],
            'Server Error HTTP 500' => [500, self::RESP_SERVER_ERROR],
            'Server Error HTTP 200' => [200, self::RESP_SERVER_ERROR],
            'HTTP Bad Request de SUNAT' => [200, self::RESP_HTTP_BAD_REQUEST],
            'HTTP Bad Request con 501' => [501, self::RESP_HTTP_BAD_REQUEST],
            'sin respuesta (timeout)' => [0, ''],
            'gateway HTML' => [502, '<html><body>502 Bad Gateway</body></html>'],
            'servicio no disponible sin código' => [200, '{"isSuccess":false,"mensaje":"El servicio de SUNAT no está disponible"}'],
            '0130 ticket' => [200, '{"isSuccess":false,"code":"0130","errores":"El sistema no puede responder su solicitud. (No se pudo obtener el ticket de proceso)"}'],
            'código numérico sin ceros' => [200, '{"isSuccess":false,"code":109}'],
        ];
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function rechazosReales(): array
    {
        return [
            '1033 registrado previamente' => [200, self::RESP_1033],
            '1033 con HTTP 501' => [501, self::RESP_1033],
            '3462 contenido' => [200, self::RESP_3462],
            '0102 credenciales' => [200, self::RESP_0102],
            // Con código de contenido manda el código, aunque el HTTP sea 5xx.
            '2108 con HTTP 500' => [500, '{"isSuccess":false,"code":"2108","errores":"Presentacion fuera de fecha"}'],
            'sin código ni texto de servicio' => [200, '{"isSuccess":false,"errores":"El XML no contiene el tag cbc:IssueDate"}'],
        ];
    }

    /**
     * @dataProvider transitorias
     */
    public function testIndisponibilidadEsTransitoria(int $http, string $body): void
    {
        $resp = json_decode($body, true) ?: [];
        $this->assertTrue(FiscalTransientErrorClassifier::isTransientPseResponse($http, $resp, $body));
    }

    /**
     * @dataProvider rechazosReales
     */
    public function testRechazoRealNoEsTransitorio(int $http, string $body): void
    {
        $resp = json_decode($body, true) ?: [];
        $this->assertFalse(FiscalTransientErrorClassifier::isTransientPseResponse($http, $resp, $body));
    }

    public function testAceptadoNuncaEsTransitorio(): void
    {
        $resp = ['isSuccess' => true, 'estado' => 200, 'code' => '0', 'mensaje' => 'La Boleta numero B003-893, ha sido aceptada'];
        $this->assertFalse(FiscalTransientErrorClassifier::isTransientPseResponse(200, $resp));
    }

    public function testNormalizaCodigos(): void
    {
        $this->assertSame('0109', FiscalTransientErrorClassifier::normalizeCode('109'));
        $this->assertSame('0109', FiscalTransientErrorClassifier::normalizeCode('soap-env:Client.0109'));
        $this->assertSame('HTTP', FiscalTransientErrorClassifier::normalizeCode('http'));
        $this->assertNull(FiscalTransientErrorClassifier::normalizeCode('  '));
    }

    public function testErrorSunatDirecto(): void
    {
        $this->assertTrue(FiscalTransientErrorClassifier::isTransientSunatError('0109', 'El sistema no puede responder su solicitud.'));
        $this->assertTrue(FiscalTransientErrorClassifier::isTransientSunatError('HTTP', 'Service Unavailable'));
        $this->assertTrue(FiscalTransientErrorClassifier::isTransientSunatError('error', 'Could not connect to host'));
        $this->assertFalse(FiscalTransientErrorClassifier::isTransientSunatError('2335', 'El documento electrónico ingresado ha sido alterado'));
        // 0102: credenciales. Reintentar no lo arregla, y no debe confundirse con "autenticación no disponible".
        $this->assertFalse(FiscalTransientErrorClassifier::isTransientSunatError('0102', 'Usuario o contraseña incorrectos'));
        $this->assertFalse(FiscalTransientErrorClassifier::isTransientSunatError(null, null));
    }

    public function testProveedorValidaPseNoRechazaPorIndisponibilidad(): void
    {
        $provider = $this->provider();

        foreach ([[200, self::RESP_0109, '0109'], [500, self::RESP_SERVER_ERROR, null], [200, self::RESP_HTTP_BAD_REQUEST, 'HTTP']] as [$http, $body, $code]) {
            $r = $provider->resultFromHttpResponse($http, $body, '<xml/>', '20614224682-03-B003-895');
            $this->assertTrue($r->transient, $body);
            $this->assertFalse($r->rejected, $body);
            $this->assertFalse($r->success, $body);
            $this->assertSame($code, $r->sunatCode, $body);
            $this->assertStringStartsWith('PSE/SUNAT no disponible', (string) $r->sunatMessage);
            $this->assertNotSame([], $r->pseResponse);
        }

        $timeout = $provider->resultFromHttpResponse(0, '', '<xml/>', 'x');
        $this->assertTrue($timeout->transient);
        $this->assertSame(0, $timeout->pseResponse['http_status']);
    }

    public function testProveedorValidaPseSigueRechazandoErroresDeContenido(): void
    {
        $r = $this->provider()->resultFromHttpResponse(200, self::RESP_1033, '<xml/>', 'x');
        $this->assertTrue($r->rejected);
        $this->assertFalse($r->transient);
        // El mensaje de ValidaPSE viene en "errores": antes se perdía y quedaba "Rechazado por PSE".
        $this->assertStringStartsWith('1033 - El comprobante fue registrado previamente', (string) $r->sunatMessage);
    }

    private function provider(): ValidaPseProvider
    {
        return new ValidaPseProvider(
            $this->createMock(PseAuthBuilder::class),
            new XmlBuilderResolverFactory('', '')
        );
    }
}
