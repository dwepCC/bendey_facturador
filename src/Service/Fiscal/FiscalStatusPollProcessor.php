<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use App\Entity\FiscalDocument;
use App\Repository\EmpresaRepository;
use App\Repository\FiscalDocumentRepository;
use App\Service\SeeFactory;
use Doctrine\ORM\EntityManagerInterface;
use Greenter\Model\Summary\Summary;
use Greenter\Model\Voided\Reversion;
use Greenter\Model\Voided\Voided;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

/**
 * Consulta ticket SUNAT para resúmenes/bajas/reversiones (documentos asíncronos).
 */
class FiscalStatusPollProcessor
{
    private EntityManagerInterface $em;
    private FiscalDocumentRepository $repo;
    private EmpresaRepository $empresaRepo;
    private SerializerInterface $serializer;
    private FiscalStorageService $storage;
    private FiscalWebhookService $webhook;
    private FiscalQueueService $queue;
    private SeeFactory $seeFactory;
    private LoggerInterface $logger;
    private ?FiscalDocumentPdfResolver $pdfResolver;

    public function __construct(
        EntityManagerInterface $em,
        FiscalDocumentRepository $repo,
        EmpresaRepository $empresaRepo,
        SerializerInterface $serializer,
        FiscalStorageService $storage,
        FiscalWebhookService $webhook,
        FiscalQueueService $queue,
        SeeFactory $seeFactory,
        LoggerInterface $logger,
        ?FiscalDocumentPdfResolver $pdfResolver = null
    ) {
        $this->em = $em;
        $this->repo = $repo;
        $this->empresaRepo = $empresaRepo;
        $this->serializer = $serializer;
        $this->storage = $storage;
        $this->webhook = $webhook;
        $this->queue = $queue;
        $this->seeFactory = $seeFactory;
        $this->logger = $logger;
        $this->pdfResolver = $pdfResolver;
    }

    public function processByUuid(string $documentUuid, int $attempt = 1): void
    {
        $doc = $this->repo->findOneBy(['documentUuid' => $documentUuid]);
        if ($doc === null || $doc->getTicket() === null || $doc->getTicket() === '') {
            return;
        }
        if ($doc->getStatus() === FiscalDocument::STATUS_ACCEPTED || $doc->getStatus() === FiscalDocument::STATUS_OBSERVED) {
            return;
        }

        try {
            [$class, $greenterDoc, $ruc] = $this->deserialize($doc);
            if (!FiscalDocumentClassResolver::isTicketBased($class)) {
                return;
            }

            $see = $this->seeFactory->build($class, $ruc);
            $result = $see->getStatus($doc->getTicket());
            if (!method_exists($result, 'isSuccess') || !$result->isSuccess()) {
                $this->scheduleRetry($doc, $attempt);
                return;
            }

            $signedXml = '';
            if (method_exists($see, 'getFactory')) {
                $signedXml = (string) $see->getFactory()->getLastXml();
            }
            $cdrZip = null;
            if (method_exists($result, 'getCdrZip') && $result->getCdrZip()) {
                $cdrZip = $result->getCdrZip();
            }

            if ($signedXml !== '') {
                $stored = $this->storage->store(
                    $doc->getTenantSlug(),
                    $doc->getDocumentType(),
                    $doc->getSeries(),
                    $doc->getNumber(),
                    null,
                    $signedXml,
                    $cdrZip
                );
                $doc->setXmlUrl($stored['xml_url']);
                $doc->setXmlSignedUrl($stored['xml_signed_url']);
                $doc->setCdrUrl($stored['cdr_url']);
                if ($this->pdfResolver !== null) {
                    $this->pdfResolver->generate($doc, true);
                }
            }

            $cdr = method_exists($result, 'getCdrResponse') ? $result->getCdrResponse() : null;
            if ($cdr instanceof \Greenter\Model\Response\CdrResponse) {
                $classified = \App\Service\Fiscal\Provider\SunatCdrClassifier::fromCdrResponse($cdr);
                $doc->setSunatCode($classified['code']);
                $doc->setSunatMessage($classified['message']);
                if (!empty($classified['notes'])) {
                    $doc->setPseResponseJson(json_encode(['cdr_notes' => $classified['notes']], JSON_UNESCAPED_UNICODE) ?: null);
                }
                if ($classified['observed']) {
                    $doc->setStatus(FiscalDocument::STATUS_OBSERVED);
                    $doc->setAcceptedAt(new \DateTimeImmutable());
                } elseif ($classified['success']) {
                    $doc->setStatus(FiscalDocument::STATUS_ACCEPTED);
                    $doc->setAcceptedAt(new \DateTimeImmutable());
                } else {
                    $doc->setStatus(FiscalDocument::STATUS_REJECTED);
                    $doc->setRejectedAt(new \DateTimeImmutable());
                }
            } else {
                $doc->setStatus(FiscalDocument::STATUS_ACCEPTED);
                $doc->setSunatCode('0');
                $doc->setAcceptedAt(new \DateTimeImmutable());
            }
            $this->em->flush();
            $this->notifyOrEnqueueSync($doc);

            if ($empresa !== null
                && in_array($doc->getStatus(), [FiscalDocument::STATUS_ACCEPTED, FiscalDocument::STATUS_OBSERVED], true)) {
                FiscalEmailDispatchHelper::enqueueOrMarkUnavailable($doc, $empresa, $this->queue);
            }
        } catch (\Throwable $e) {
            $this->logger->error('fiscal_status_poll_failed', [
                'uuid' => $documentUuid,
                'attempt' => $attempt,
                'error' => $e->getMessage(),
            ]);
            $this->scheduleRetry($doc, $attempt);
        }
    }

    /**
     * @return array{0: string, 1: object, 2: string}
     */
    private function deserialize(FiscalDocument $doc): array
    {
        $data = json_decode($doc->getSnapshotJson(), true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException('snapshot_json inválido');
        }
        if (isset($data['document']) && is_array($data['document'])) {
            $data = $data['document'];
        }
        $class = FiscalDocumentClassResolver::resolve($data, $doc);
        $greenterDoc = $this->serializer->deserialize(json_encode($data), $class, 'json');
        $ruc = trim((string) $greenterDoc->getCompany()->getRuc());
        return [$class, $greenterDoc, $ruc];
    }

    private function scheduleRetry(FiscalDocument $doc, int $attempt): void
    {
        if ($attempt >= 10) {
            $doc->setStatus(FiscalDocument::STATUS_ERROR);
            $doc->setSunatMessage('Timeout consultando ticket SUNAT');
            $this->em->flush();
            $this->notifyOrEnqueueSync($doc);
            return;
        }
        $doc->setRetryCount($doc->getRetryCount() + 1);
        $this->em->flush();
        $delay = min(3600, 60 * $attempt);
        $this->queue->scheduleRetry($doc->getDocumentUuid(), $delay, FiscalQueueService::QUEUE_STATUS_POLL);
    }

    private function notifyOrEnqueueSync(FiscalDocument $doc): void
    {
        try {
            $this->webhook->notifyStatus($doc);
        } catch (\Throwable $e) {
            $this->queue->push(FiscalQueueService::QUEUE_WEBHOOK_SYNC, [
                'document_uuid' => $doc->getDocumentUuid(),
                'attempt' => 1,
            ]);
        }
    }
}
