<?php

declare(strict_types=1);

namespace App\Tests\Service\Fiscal;

use App\Service\Fiscal\FiscalNoteSnapshotNormalizer;
use PHPUnit\Framework\TestCase;

class FiscalNoteSnapshotNormalizerTest extends TestCase
{
    public function testCopiesRelDocsToAffectedFields(): void
    {
        $out = FiscalNoteSnapshotNormalizer::normalize([
            'tipoDoc' => '07',
            'relDocs' => [
                ['tipoDoc' => '03', 'nroDoc' => 'B001-00000012'],
            ],
        ]);

        $this->assertSame('03', $out['tipDocAfectado']);
        $this->assertSame('B001-00000012', $out['numDocfectado']);
        $this->assertArrayNotHasKey('relDocs', $out);
    }

    public function testSkipsWhenAlreadyPresent(): void
    {
        $in = [
            'tipoDoc' => '07',
            'tipDocAfectado' => '01',
            'numDocfectado' => 'F001-00000001',
            'formaPago' => ['tipo' => 'Contado'],
            'relDocs' => [['tipoDoc' => '03', 'nroDoc' => 'B001-1']],
        ];
        $out = FiscalNoteSnapshotNormalizer::normalize($in);
        $this->assertSame('01', $out['tipDocAfectado']);
        $this->assertSame('F001-00000001', $out['numDocfectado']);
        $this->assertArrayNotHasKey('formaPago', $out);
        $this->assertArrayNotHasKey('relDocs', $out);
    }

    public function testStripsFormaPagoFromCreditNote(): void
    {
        $out = FiscalNoteSnapshotNormalizer::normalize([
            'tipoDoc' => '07',
            'formaPago' => ['tipo' => 'Contado'],
        ]);
        $this->assertArrayNotHasKey('formaPago', $out);
    }
}
