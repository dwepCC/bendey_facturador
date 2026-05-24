<?php

declare(strict_types=1);

namespace App\Tests\Service\Fiscal;

use App\Entity\FiscalDocument;
use App\Service\Fiscal\FiscalDocumentClassResolver;
use Greenter\Model\Despatch\Despatch;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Note;
use Greenter\Model\Summary\Summary;
use Greenter\Model\Voided\Voided;
use PHPUnit\Framework\TestCase;

class FiscalDocumentClassResolverTest extends TestCase
{
    public function testResolveInvoiceAndNote(): void
    {
        $doc = new FiscalDocument();
        $doc->setDocumentType('03');
        self::assertSame(Invoice::class, FiscalDocumentClassResolver::resolve(['tipoDoc' => '03'], $doc));
        $doc->setDocumentType('07');
        self::assertSame(Note::class, FiscalDocumentClassResolver::resolve(['tipoDoc' => '07'], $doc));
    }

    public function testResolveExtendedTypes(): void
    {
        $doc = new FiscalDocument();
        self::assertSame(Despatch::class, FiscalDocumentClassResolver::resolve(['tipoDoc' => '09'], $doc));
        self::assertSame(Summary::class, FiscalDocumentClassResolver::resolve(['tipoDoc' => 'RC'], $doc));
        self::assertSame(Voided::class, FiscalDocumentClassResolver::resolve(['_meta' => ['document_kind' => 'voided']], $doc));
    }
}
