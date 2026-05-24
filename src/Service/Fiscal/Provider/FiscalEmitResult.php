<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Provider;

use App\Entity\Empresa;
use App\Entity\FiscalDocument;

/**
 * Resultado normalizado de emisión fiscal (SUNAT directo o PSE).
 */
class FiscalEmitResult
{
    public bool $success = false;
    public ?string $sunatCode = null;
    public ?string $sunatMessage = null;
    public ?string $pseMessage = null;
    public ?string $hash = null;
    public ?string $ticket = null;
    public ?string $unsignedXml = null;
    public ?string $signedXml = null;
    public ?string $cdrZip = null;
    public ?string $pdf = null;
    /** @var array<string, mixed> */
    public array $pseResponse = [];
    /** @var array<string, mixed> */
    public array $sunatResponse = [];
    /** @var array<int, string> Notas del CDR (cbc:Note). */
    public array $cdrNotes = [];
    public bool $rejected = false;
    /** Documento válido ante SUNAT pero con observaciones (código >= 4000 o notas CDR). */
    public bool $observed = false;

    public function isAccepted(): bool
    {
        return $this->success && !$this->rejected && !$this->observed;
    }

    public function isObserved(): bool
    {
        return $this->observed;
    }

    public function hasSunatOutcome(): bool
    {
        return $this->isAccepted() || $this->isObserved() || $this->rejected;
    }
}
