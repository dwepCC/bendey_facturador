<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Verification;

/**
 * GET /api/cpe/consultar/{RUC-TIPO-SERIE-NUMERO} de ValidaPSE (recuperar el CDR). Solo lectura.
 */
interface ValidaPseConsultClientInterface
{
    /**
     * @return array{http: int, body: string}
     */
    public function consultar(string $baseUrl, string $token, string $nombreArchivo): array;
}
