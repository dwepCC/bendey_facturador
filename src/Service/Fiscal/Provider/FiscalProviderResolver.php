<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Provider;

use App\Entity\Empresa;
use App\Entity\FiscalDocument;
use Greenter\Model\DocumentInterface;

/**
 * Resuelve proveedor fiscal según send_mode / provider del documento o empresa.
 */
class FiscalProviderResolver
{
    /** @var FiscalProviderInterface[] */
    private iterable $providers;

    /**
     * @param FiscalProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    /**
     * @param class-string $documentClass
     */
    public function emit(
        FiscalDocument $doc,
        Empresa $empresa,
        string $documentClass,
        DocumentInterface $greenterDoc
    ): FiscalEmitResult {
        $this->applyEmpresaMode($doc, $empresa);
        foreach ($this->providers as $provider) {
            if ($provider->supports($doc, $empresa)) {
                return $provider->emit($doc, $empresa, $documentClass, $greenterDoc);
            }
        }
        throw new \RuntimeException('No hay proveedor fiscal para send_mode=' . ($doc->getSendMode() ?? 'null'));
    }

    public function resolveName(FiscalDocument $doc, Empresa $empresa): string
    {
        $this->applyEmpresaMode($doc, $empresa);
        foreach ($this->providers as $provider) {
            if ($provider->supports($doc, $empresa)) {
                return $provider->getName();
            }
        }
        return 'unknown';
    }

    public function validateConnection(Empresa $empresa): FiscalConnectionResult
    {
        foreach ($this->providers as $provider) {
            $doc = new FiscalDocument();
            $doc->setSendMode($empresa->getSendMode());
            $doc->setProvider($empresa->getProvider());
            if ($provider->supports($doc, $empresa)) {
                return $provider->validateConnection($empresa);
            }
        }
        return FiscalConnectionResult::fail('configuration_missing', 'No hay proveedor para send_mode=' . $empresa->getSendMode());
    }

    private function applyEmpresaMode(FiscalDocument $doc, Empresa $empresa): void
    {
        $doc->setSendMode($empresa->getSendMode());
        $doc->setProvider($empresa->getProvider());
        $doc->setSunatMode($this->ambienteToSunatMode($empresa->getAmbiente()));
    }

    private function ambienteToSunatMode(string $ambiente): string
    {
        return strtolower(trim($ambiente)) === 'produccion' ? 'production' : 'beta';
    }
}
