<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use App\Entity\FiscalDocument;
use Greenter\Model\Despatch\Despatch;
use Greenter\Model\Perception\Perception;
use Greenter\Model\Retention\Retention;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Note;
use Greenter\Model\Summary\Summary;
use Greenter\Model\Voided\Reversion;
use Greenter\Model\Voided\Voided;

/**
 * Mapea snapshot JSON → clase Greenter (factura, NC/ND, guía, resumen, baja).
 */
final class FiscalDocumentClassResolver
{
    /**
     * @param array<string, mixed> $data
     */
    public static function resolve(array $data, FiscalDocument $doc): string
    {
        $kind = strtolower(trim((string) ($data['_meta']['document_kind'] ?? $data['document_kind'] ?? '')));
        if ($kind !== '') {
            switch ($kind) {
                case 'invoice':
                case 'factura':
                case 'boleta':
                    return Invoice::class;
                case 'note':
                case 'nota':
                    return Note::class;
                case 'despatch':
                case 'guia':
                case 'guia_remision':
                case 'guia_transportista':
                    return Despatch::class;
                case 'retention':
                case 'retencion':
                    return Retention::class;
                case 'perception':
                case 'percepcion':
                    return Perception::class;
                case 'summary':
                case 'resumen':
                    return Summary::class;
                case 'voided':
                case 'baja':
                    return Voided::class;
                case 'reversion':
                    return Reversion::class;
            }
        }

        $tipo = strtoupper(trim((string) ($data['tipoDoc'] ?? $doc->getDocumentType())));
        switch ($tipo) {
            case '07':
            case '08':
                return Note::class;
            case '09':
            case '31':
                return Despatch::class;
            case '20':
                return Retention::class;
            case '40':
                return Perception::class;
            case 'RC':
                return Summary::class;
            case 'RA':
                return Voided::class;
            case 'RR':
                return Reversion::class;
            default:
                return Invoice::class;
        }
    }

    public static function isTicketBased(string $documentClass): bool
    {
        return in_array($documentClass, [Summary::class, Voided::class, Reversion::class, Despatch::class], true);
    }

    public static function supportsPdf(string $documentClass): bool
    {
        return in_array($documentClass, [Invoice::class, Note::class, Despatch::class, Retention::class, Perception::class], true);
    }
}
