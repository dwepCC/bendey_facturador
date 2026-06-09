<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use App\Entity\FiscalDocument;
use App\Repository\EmpresaRepository;
use App\Repository\FiscalDocumentRepository;
use App\Service\SeeApiFactory;
use App\Service\SeeFactory;
use Doctrine\ORM\EntityManagerInterface;
use Greenter\Model\Despatch\Despatch;
use Greenter\Model\Summary\Summary;
use Greenter\Model\Voided\Reversion;
use Greenter\Model\Voided\Voided;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

/**
 * Consulta ticket SUNAT para documentos asíncronos (resumen/baja/reversión/GRE REST).
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
    private SeeApiFactory $seeApiFactory;
    private FiscalFileFetcher $fileFetcher;
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
        SeeApiFactory $seeApiFactory,
        FiscalFileFetcher $fileFetcher,
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
        $this->seeApiFactory = $seeApiFactory;
        $this->fileFetcher = $fileFetcher;
        $this->logger = $logger;
        $this->pdfResolver = $pdfResolver;
    }

    public function processByUuid(string $documentUuid, int $attempt = 1): void
    {
        $doc = $this->repo->findOneBy(['documentUuid' => $documentUuid]);
        if ($doc === null || $doc->getTicket() === null || $doc->getTicket() === '') {
            return;
        }
        if ($doc->getStatus() === FiscalDocument::STATUS_ACCEPTED
            || $doc->getStatus() === FiscalDocument::STATUS_OBSERVED
            || $doc->getStatus() === FiscalDocument::STATUS_REJECTED) {
            return;
        }

        try {
            [$class, $greenterDoc, $ruc] = $this->deserialize($doc);
            if (!FiscalDocumentClassResolver::isTicketBased($class)) {
                return;
            }

            $empresa = $this->empresaRepo->findByRuc($ruc);

            if ($class === Despatch::class) {
                $api = $this->seeApiFactory->build($ruc);
                $result = $api->getStatus($doc->getTicket());
            } else {
                $see = $this->seeFactory->build($class, $ruc);
                $result = $see->getStatus($doc->getTicket());
            }

            $statusCode = method_exists($result, 'getCode') ? trim((string) $result->getCode()) : '';

            // 98 = en proceso (GRE REST / SUNAT async).
            if ($statusCode === '98') {
                $this->scheduleRetry($doc, $attempt);
                return;
            }

            if (!method_exists($result, 'isSuccess') || !$result->isSuccess()) {
                if ($statusCode === '99' || $result->getError() !== null) {
                    $this->applyPollRejection($doc, $result, $statusCode);
                    return;
                }
                $this->scheduleRetry($doc, $attempt);
                return;
            }

            $cdrZip = null;
            if (method_exists($result, 'getCdrZip') && $result->getCdrZip()) {
                $cdrZip = $result->getCdrZip();
            }

            if ($cdrZip !== null && $cdrZip !== '') {
                $signedXml = $this->resolveSignedXmlForPoll($doc, $class, $ruc);
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

    private function resolveSignedXmlForPoll(FiscalDocument $doc, string $class, string $ruc): string
    {
        $fetched = $this->fileFetcher->fetch($doc->getXmlSignedUrl());
        if ($fetched !== null && ($fetched['content'] ?? '') !== '') {
            return (string) $fetched['content'];
        }

        if ($class !== Despatch::class) {
            try {
                $see = $this->seeFactory->build($class, $ruc);
                return (string) $see->getFactory()->getLastXml();
            } catch (\Throwable) {
                return '';
            }
        }

        return '';
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
        $this->queue->scheduleRetry($doc->getDocumentUuid(), $delay, FiscalQueueService::QUEUE_STATUS_POLL_RETRY);
    }

    /**
     * @param \Greenter\Model\Response\StatusResult|\Greenter\Model\Response\BaseResult $result
     */
    private function applyPollRejection(FiscalDocument $doc, object $result, string $statusCode): void
    {
        $err = method_exists($result, 'getError') ? $result->getError() : null;
        $doc->setStatus(FiscalDocument::STATUS_REJECTED);
        $doc->setRejectedAt(new \DateTimeImmutable());
        if ($err !== null) {
            $doc->setSunatCode((string) $err->getCode());
            $doc->setSunatMessage((string) $err->getMessage());
        } else {
            $doc->setSunatCode($statusCode !== '' ? $statusCode : '99');
            $doc->setSunatMessage('Rechazado por SUNAT');
        }
        $this->em->flush();
        $this->notifyOrEnqueueSync($doc);
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
