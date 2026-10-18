<?php

declare(strict_types=1);

namespace App\Tests\Service\Fiscal;

use App\Entity\Empresa;
use App\Entity\FiscalDocument;
use App\Greenter\Factory\XmlBuilderResolverFactory;
use App\Repository\EmpresaRepository;
use App\Repository\FiscalDocumentRepository;
use App\Service\Fiscal\FiscalEmitProcessor;
use App\Service\Fiscal\FiscalPdfService;
use App\Service\Fiscal\FiscalQueueService;
use App\Service\Fiscal\FiscalStorageService;
use App\Service\Fiscal\FiscalWebhookService;
use App\Service\Fiscal\Provider\FiscalEmitResult;
use App\Service\Fiscal\Provider\FiscalProviderResolver;
use App\Service\Fiscal\Provider\PseAuthBuilder;
use App\Service\Fiscal\Provider\ValidaPseProvider;
use Doctrine\ORM\EntityManagerInterface;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\Invoice;
use JMS\Serializer\SerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * El procesador ya no deja `rejected` un comprobante que SUNAT/PSE no pudo atender:
 * queda `retrying` con el mismo número y, agotados los intentos, `error` sin código SUNAT
 * (backend_go lee `error` + código distinto de 0 como rechazo y no permite reenviarlo).
 */
class FiscalEmitProcessorTransientTest extends TestCase
{
    private const RUC = '20614224682';

    /** @var FiscalQueueService&MockObject */
    private $queue;
    /** @var FiscalWebhookService&MockObject */
    private $webhook;

    public function testCaida0109QuedaEnReintentoConElMismoNumero(): void
    {
        $doc = $this->doc();
        $this->queue->expects($this->once())->method('scheduleRetry')
            ->with($doc->getDocumentUuid(), 30, FiscalQueueService::QUEUE_PSE_RETRY);
        $this->webhook->expects($this->never())->method('notifyStatus');

        $this->processor($this->pseResult(200, '{"isSuccess":false,"estado":501,"code":"0109","errores":"El sistema no puede responder su solicitud. (El servicio de autenticación no está disponible)"}'))
            ->process($doc);

        $this->assertSame(FiscalDocument::STATUS_RETRYING, $doc->getStatus());
        $this->assertNull($doc->getSunatCode());
        $this->assertNull($doc->getRejectedAt());
        $this->assertSame('895', $doc->getNumber());
        $this->assertSame(1, $doc->getRetryCount());
        $this->assertNotNull($doc->getNextRetryAt());
        $this->assertStringContainsString('0109', (string) $doc->getSunatMessage());
        $this->assertStringContainsString('"code":"0109"', (string) $doc->getPseResponseJson());
    }

    public function testServerErrorAgotadoQuedaErrorReenviableNoRechazado(): void
    {
        $doc = $this->doc();
        $doc->setRetryCount(7);
        // Un rechazo anterior del mismo documento no debe sobrevivir al nuevo intento.
        $doc->setSunatCode('501');
        $doc->setRejectedAt(new \DateTimeImmutable());
        $this->queue->expects($this->never())->method('scheduleRetry');
        $this->webhook->expects($this->once())->method('notifyStatus');

        $this->processor($this->pseResult(500, '{"message":"Server Error"}'))->process($doc);

        $this->assertSame(FiscalDocument::STATUS_ERROR, $doc->getStatus());
        $this->assertNull($doc->getSunatCode());
        $this->assertNull($doc->getRejectedAt());
        $this->assertSame(8, $doc->getRetryCount());
    }

    public function testSinReintentosHabilitadosQuedaError(): void
    {
        $doc = $this->doc();
        $this->queue->expects($this->never())->method('scheduleRetry');
        $this->webhook->expects($this->once())->method('notifyStatus');

        $this->processor($this->pseResult(200, '{"isSuccess":false,"estado":501,"code":"HTTP","errores":"Bad Request","xml":""}'), false)
            ->process($doc);

        $this->assertSame(FiscalDocument::STATUS_ERROR, $doc->getStatus());
        $this->assertNull($doc->getSunatCode());
    }

    public function testRechazoRealSigueSiendoRechazo(): void
    {
        $doc = $this->doc();
        $this->queue->expects($this->never())->method('scheduleRetry');

        $this->processor($this->pseResult(200, '{"isSuccess":false,"estado":501,"code":"1033","errores":"1033 - El comprobante fue registrado previamente con otros datos"}'))
            ->process($doc);

        $this->assertSame(FiscalDocument::STATUS_REJECTED, $doc->getStatus());
        $this->assertSame('501', $doc->getSunatCode());
    }

    public function testBackoff(): void
    {
        $this->assertSame([30, 60, 120, 240, 480, 960, 1920, 3600], array_map(
            [FiscalEmitProcessor::class, 'retryDelaySeconds'],
            [1, 2, 3, 4, 5, 6, 7, 9]
        ));
    }

    protected function setUp(): void
    {
        $this->queue = $this->createMock(FiscalQueueService::class);
        $this->webhook = $this->createMock(FiscalWebhookService::class);
    }

    private function pseResult(int $http, string $body): FiscalEmitResult
    {
        $provider = new ValidaPseProvider($this->createMock(PseAuthBuilder::class), new XmlBuilderResolverFactory('', ''));

        return $provider->resultFromHttpResponse($http, $body, '<xml/>', self::RUC . '-03-B003-895');
    }

    private function processor(FiscalEmitResult $result, bool $retryEnabled = true): FiscalEmitProcessor
    {
        $empresa = new Empresa();
        $empresa->setRuc(self::RUC);
        $empresa->setSendMode('pse');
        $empresa->setProvider('validapse');
        $empresa->setAmbiente('produccion');
        $empresa->setEnabled(true);
        $empresa->setRetryEnabled($retryEnabled);

        $empresaRepo = $this->createMock(EmpresaRepository::class);
        $empresaRepo->method('find')->willReturn($empresa);

        $invoice = new Invoice();
        $invoice->setCompany((new Company())->setRuc(self::RUC));
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('deserialize')->willReturn($invoice);

        $resolver = $this->createMock(FiscalProviderResolver::class);
        $resolver->method('resolveName')->willReturn('validapse');
        $resolver->method('emit')->willReturn($result);

        return new FiscalEmitProcessor(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(FiscalDocumentRepository::class),
            $empresaRepo,
            $serializer,
            $this->createMock(FiscalStorageService::class),
            $this->webhook,
            $this->queue,
            $resolver,
            $this->createMock(FiscalPdfService::class),
            new NullLogger()
        );
    }

    private function doc(): FiscalDocument
    {
        $doc = new FiscalDocument();
        $doc->setDocumentUuid('26ed255a-fe20-4736-9ccd-86df773ad32e');
        $doc->setTenantId(1);
        $doc->setTenantSlug('comercializadoradecalzadosadri');
        $doc->setSaleId(696);
        $doc->setDocumentType('03');
        $doc->setSeries('B003');
        $doc->setNumber('895');
        $doc->setSnapshotJson(json_encode([
            'tipoDoc' => '03',
            'serie' => 'B003',
            'correlativo' => '895',
            'company' => ['ruc' => self::RUC],
        ], JSON_THROW_ON_ERROR));
        $doc->setStatus(FiscalDocument::STATUS_QUEUED);

        return $doc;
    }
}
