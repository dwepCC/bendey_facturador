<?php

declare(strict_types=1);

namespace App\Tests\Service\Fiscal;

use App\Entity\FiscalDocument;
use App\Repository\FiscalDocumentRepository;
use App\Service\Fiscal\FiscalCustomerEmailNormalizer;
use App\Service\Fiscal\FiscalEmailProcessor;
use App\Service\Fiscal\FiscalMailerService;
use App\Service\Fiscal\FiscalQueueService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class FiscalEmailProcessorTest extends TestCase
{
    public function testNullEmailMarksNotAvailableWithoutSending(): void
    {
        $doc = new FiscalDocument();
        $doc->setDocumentUuid('00000000-0000-0000-0000-000000000001');
        $doc->setTenantId(1);
        $doc->setTenantSlug('tenant-a');
        $doc->setSaleId(1);
        $doc->setDocumentType('03');
        $doc->setSeries('B001');
        $doc->setNumber('1');
        $doc->setSnapshotJson(json_encode(['client' => ['email' => null]], JSON_THROW_ON_ERROR));
        $doc->setStatus(FiscalDocument::STATUS_ACCEPTED);

        $repo = $this->createMock(FiscalDocumentRepository::class);
        $repo->method('findOneBy')->willReturn($doc);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $mailer = $this->createMock(FiscalMailerService::class);
        $mailer->expects($this->never())->method('sendFiscalDocument');

        $processor = new FiscalEmailProcessor(
            $em,
            $repo,
            new FiscalQueueService(null),
            $mailer,
            new NullLogger()
        );

        $processor->processByUuid($doc->getDocumentUuid());

        $this->assertSame(FiscalCustomerEmailNormalizer::STATUS_NOT_AVAILABLE, $doc->getEmailStatus());
    }

    public function testValidEmailIsDeliverable(): void
    {
        $processor = new FiscalEmailProcessor(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(FiscalDocumentRepository::class),
            new FiscalQueueService(null),
            $this->createMock(FiscalMailerService::class),
            new NullLogger()
        );

        $doc = new FiscalDocument();
        $doc->setCustomerEmail('cliente@example.com');
        $doc->setSnapshotJson('{}');

        $this->assertSame('cliente@example.com', $processor->resolveDeliverableEmail($doc));
    }
}
