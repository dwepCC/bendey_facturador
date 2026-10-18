<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Verification;

/**
 * API REST «Consulta Integrada de Comprobante de Pago» de SUNAT (manual v2.0). Solo lectura.
 * Dice si el comprobante existe y es válido (estadoCp 0/1/2/4); no entrega CDR.
 */
interface SunatValidezApiClientInterface
{
    /**
     * @param array{numRuc: string, codComp: string, numeroSerie: string, numero: int, fechaEmision: string, monto?: float} $consulta
     * @return array{http: int, body: string}
     */
    public function validar(string $clientId, string $clientSecret, string $rucConsulta, array $consulta): array;
}
