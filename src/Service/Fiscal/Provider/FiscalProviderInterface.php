<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Provider;

use App\Entity\Empresa;
use App\Entity\FiscalDocument;
use Greenter\Model\DocumentInterface;

/**
 * Contrato de proveedor fiscal extensible (SUNAT directo, PSE, futuros OSE).
 */
interface FiscalProviderInterface
{
    public function getName(): string;

    public function supports(FiscalDocument $doc, Empresa $empresa): bool;

    public function validateConnection(Empresa $empresa): FiscalConnectionResult;

    /**
     * Emite documento fiscal.
     *
     * @param class-string $documentClass Invoice::class | Note::class
     */
    public function emit(
        FiscalDocument $doc,
        Empresa $empresa,
        string $documentClass,
        DocumentInterface $greenterDoc
    ): FiscalEmitResult;

    /**
     * Consulta estado en proveedor externo (opcional por implementación).
     *
     * @return array<string, mixed>|null
     */
    public function queryStatus(FiscalDocument $doc, Empresa $empresa): ?array;

    /**
     * Anula/baja documento (opcional por implementación).
     */
    public function cancel(FiscalDocument $doc, Empresa $empresa): FiscalEmitResult;
}
