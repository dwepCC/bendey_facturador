<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Verification;

use App\Entity\FiscalDocument;

/**
 * Lo que respondió SUNAT (o ValidaPSE) al consultar un comprobante. Solo describe: aplicarlo es
 * una decisión del operador del panel (FiscalSunatVerifier::apply).
 */
final class VerificationResult
{
    public const ACCEPTED = 'accepted';
    public const OBSERVED = 'observed';
    public const REJECTED = 'rejected';
    /** SUNAT lo tiene de baja (comunicación de baja / resumen). */
    public const VOIDED = 'voided';
    /** SUNAT no lo tiene: el número sigue libre y puede reenviarse. */
    public const NOT_FOUND = 'not_found';
    /** No se pudo consultar (credenciales, servicio caído, ambiente de pruebas…). */
    public const UNAVAILABLE = 'unavailable';

    public const SOURCE_SUNAT_CDR = 'sunat_cdr';
    public const SOURCE_SUNAT_STATUS = 'sunat_status';
    public const SOURCE_VALIDAPSE = 'validapse_consultar';
    /** API REST de validez (Consulta Integrada): confirma existencia y validez, sin CDR. */
    public const SOURCE_SUNAT_VALIDEZ = 'sunat_validez_api';

    public string $outcome = self::UNAVAILABLE;
    public ?string $source = null;
    /** Código de la consulta (0001, 0004, 0011…) o del error. */
    public ?string $statusCode = null;
    public ?string $statusMessage = null;
    /** Código y descripción del CDR, si vino. */
    public ?string $cdrCode = null;
    public ?string $cdrMessage = null;
    /** @var array<int, string> */
    public array $cdrNotes = [];
    public ?string $cdrZip = null;
    /** Explicación para el operador cuando no se pudo concluir. */
    public ?string $reason = null;
    public \DateTimeImmutable $consultedAt;

    public function __construct()
    {
        $this->consultedAt = new \DateTimeImmutable();
    }

    public static function unavailable(string $reason, ?string $source = null, ?string $code = null, ?string $message = null): self
    {
        $r = new self();
        $r->outcome = self::UNAVAILABLE;
        $r->reason = $reason;
        $r->source = $source;
        $r->statusCode = $code;
        $r->statusMessage = $message;

        return $r;
    }

    /** Solo estos resultados se pueden aplicar al documento. */
    public function isApplicable(): bool
    {
        return in_array($this->outcome, [self::ACCEPTED, self::OBSERVED, self::REJECTED], true);
    }

    public function proposedStatus(): ?string
    {
        return match ($this->outcome) {
            self::ACCEPTED => FiscalDocument::STATUS_ACCEPTED,
            self::OBSERVED => FiscalDocument::STATUS_OBSERVED,
            self::REJECTED => FiscalDocument::STATUS_REJECTED,
            default => null,
        };
    }

    public function label(): string
    {
        return match ($this->outcome) {
            self::ACCEPTED => 'SUNAT lo tiene ACEPTADO',
            self::OBSERVED => 'SUNAT lo tiene ACEPTADO con observaciones',
            self::REJECTED => 'SUNAT lo tiene RECHAZADO',
            self::VOIDED => 'SUNAT lo tiene DADO DE BAJA',
            self::NOT_FOUND => 'SUNAT NO tiene este comprobante: el número sigue libre',
            default => 'No se pudo consultar',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'outcome' => $this->outcome,
            'label' => $this->label(),
            'source' => $this->source,
            'status_code' => $this->statusCode,
            'status_message' => $this->statusMessage,
            'cdr_code' => $this->cdrCode,
            'cdr_message' => $this->cdrMessage,
            'cdr_notes' => $this->cdrNotes,
            'has_cdr' => $this->cdrZip !== null && $this->cdrZip !== '',
            'reason' => $this->reason,
            'can_apply' => $this->isApplicable(),
            'proposed_status' => $this->proposedStatus(),
            'consulted_at' => $this->consultedAt->format(DATE_ATOM),
        ];
    }
}
