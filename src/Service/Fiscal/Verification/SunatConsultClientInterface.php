<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Verification;

use Greenter\Model\Response\StatusCdrResult;

/**
 * Consulta de solo lectura al servicio billConsultService de SUNAT (clave SOL del emisor).
 * Interfaz para poder probar el verificador sin red.
 */
interface SunatConsultClientInterface
{
    /** Estado + CDR del comprobante. */
    public function getStatusCdr(string $solUser, string $solPass, string $ruc, string $tipo, string $serie, int $numero): StatusCdrResult;

    /** Solo el estado (0001 aceptado, 0002 rechazado, 0003 baja, 0011 no existe…), sin CDR. */
    public function getStatus(string $solUser, string $solPass, string $ruc, string $tipo, string $serie, int $numero): StatusCdrResult;
}
