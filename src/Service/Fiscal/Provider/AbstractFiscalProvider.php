<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Provider;

use App\Entity\Empresa;
use App\Entity\FiscalDocument;

/**
 * Implementaciones base con métodos opcionales no soportados por defecto.
 */
abstract class AbstractFiscalProvider implements FiscalProviderInterface
{
    public function queryStatus(FiscalDocument $doc, Empresa $empresa): ?array
    {
        return null;
    }

    public function cancel(FiscalDocument $doc, Empresa $empresa): FiscalEmitResult
    {
        throw new \RuntimeException('Cancel no soportado por proveedor ' . $this->getName());
    }
}
