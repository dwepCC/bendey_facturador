<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

/**
 * Normaliza snapshots NC/ND para Greenter/SUNAT.
 */
final class FiscalNoteSnapshotNormalizer
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        $tipoDoc = strtoupper(trim((string) ($data['tipoDoc'] ?? '')));
        if (!in_array($tipoDoc, ['07', '08'], true)) {
            return $data;
        }

        // SUNAT 3246: NC/ND no llevan PaymentTerms FormaPago=Contado.
        unset($data['formaPago']);

        $tip = trim((string) ($data['tipDocAfectado'] ?? ''));
        $num = trim((string) ($data['numDocfectado'] ?? ''));

        $relDocs = $data['relDocs'] ?? null;
        if (($tip === '' || $num === '') && is_array($relDocs) && $relDocs !== []) {
            $first = $relDocs[0] ?? null;
            if (is_array($first)) {
                if ($tip === '') {
                    $tip = trim((string) ($first['tipoDoc'] ?? ''));
                    if ($tip !== '') {
                        $data['tipDocAfectado'] = $tip;
                    }
                }
                if ($num === '') {
                    $num = trim((string) ($first['nroDoc'] ?? ''));
                    if ($num !== '') {
                        $data['numDocfectado'] = $num;
                    }
                }
            }
        }

        // relDocs genera AdditionalDocumentReference; 01/03 ahí provoca obs. SUNAT 4009.
        unset($data['relDocs']);

        return $data;
    }
}
