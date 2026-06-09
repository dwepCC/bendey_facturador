<?php

declare(strict_types=1);

namespace App\Tests\Service\Fiscal;

use App\Service\Fiscal\FiscalCustomerEmailNormalizer;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Note;
use PHPUnit\Framework\TestCase;

class FiscalCustomerEmailNormalizerTest extends TestCase
{
    public function testNormalizeNullEmail(): void
    {
        $this->assertNull(FiscalCustomerEmailNormalizer::normalize(null));
        $this->assertNull(FiscalCustomerEmailNormalizer::normalize(''));
        $this->assertNull(FiscalCustomerEmailNormalizer::normalize('   '));
    }

    public function testNormalizeValidEmail(): void
    {
        $this->assertSame('cliente@example.com', FiscalCustomerEmailNormalizer::normalize('  cliente@example.com  '));
        $this->assertTrue(FiscalCustomerEmailNormalizer::isDeliverable('cliente@example.com'));
    }

    public function testNullEmailUsesPlaceholderForPdfAndIsNotDeliverable(): void
    {
        $this->assertSame(
            FiscalCustomerEmailNormalizer::PLACEHOLDER,
            FiscalCustomerEmailNormalizer::forPdfDisplay(null)
        );
        $this->assertFalse(FiscalCustomerEmailNormalizer::isDeliverable(null));
        $this->assertFalse(FiscalCustomerEmailNormalizer::isDeliverable(FiscalCustomerEmailNormalizer::PLACEHOLDER));
    }

    public function testExtractFromSnapshotArray(): void
    {
        $this->assertNull(FiscalCustomerEmailNormalizer::extractFromSnapshotArray([
            'client' => ['email' => null],
        ]));
        $this->assertSame(
            'a@b.com',
            FiscalCustomerEmailNormalizer::extractFromSnapshotArray([
                'client' => ['email' => 'a@b.com'],
            ])
        );
        $this->assertSame(
            'c@d.com',
            FiscalCustomerEmailNormalizer::extractFromPayload(
                ['customer_email' => 'c@d.com'],
                ['client' => ['email' => 'a@b.com']]
            )
        );
    }

    public function testApplyPdfSafeEmailsSetsPlaceholderOnClient(): void
    {
        $invoice = new Invoice();
        $client = new Client();
        $client->setEmail(null);
        $invoice->setClient($client);

        FiscalCustomerEmailNormalizer::applyPdfSafeEmails($invoice);

        $this->assertSame(FiscalCustomerEmailNormalizer::PLACEHOLDER, $invoice->getClient()->getEmail());
    }

    public function testApplyPdfSafeEmailsWorksForCreditNoteWithoutSeller(): void
    {
        $note = new Note();
        $client = new Client();
        $client->setEmail(null);
        $note->setClient($client);
        $company = new Company();
        $company->setEmail(null);
        $note->setCompany($company);

        FiscalCustomerEmailNormalizer::applyPdfSafeEmails($note);

        $this->assertSame(FiscalCustomerEmailNormalizer::PLACEHOLDER, $note->getClient()->getEmail());
        $this->assertSame(FiscalCustomerEmailNormalizer::PLACEHOLDER, $note->getCompany()->getEmail());
    }
}
