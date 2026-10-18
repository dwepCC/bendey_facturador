<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Verification;

use Greenter\Model\Response\StatusCdrResult;
use Greenter\Ws\Services\ConsultCdrService;
use Greenter\Ws\Services\SoapClient;
use Greenter\Ws\Services\SunatEndpoints;

/**
 * billConsultService de SUNAT vía Greenter. Solo producción: SUNAT no expone consulta en beta.
 * Probado el 2026-10-18 con una boleta y una factura aceptadas: getStatusCdr devuelve el CDR de ambas.
 */
final class GreenterSunatConsultClient implements SunatConsultClientInterface
{
    public function getStatusCdr(string $solUser, string $solPass, string $ruc, string $tipo, string $serie, int $numero): StatusCdrResult
    {
        return $this->service($solUser, $solPass)->getStatusCdr($ruc, $tipo, $serie, $numero);
    }

    public function getStatus(string $solUser, string $solPass, string $ruc, string $tipo, string $serie, int $numero): StatusCdrResult
    {
        return $this->service($solUser, $solPass)->getStatus($ruc, $tipo, $serie, $numero);
    }

    private function service(string $solUser, string $solPass): ConsultCdrService
    {
        $ws = new SoapClient(SunatEndpoints::FE_CONSULTA_CDR . '?wsdl');
        $ws->setCredentials($solUser, $solPass);
        $service = new ConsultCdrService();
        $service->setClient($ws);

        return $service;
    }
}
