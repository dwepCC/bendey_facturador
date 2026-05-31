<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use App\Entity\Empresa;
use App\Entity\FiscalDocument;
use App\Entity\FiscalEmitAttempt;
use App\Exception\EmpresaNoRegistradaException;
use App\Repository\EmpresaRepository;
use App\Repository\FiscalDocumentRepository;
use App\Service\Fiscal\Provider\FiscalEmitResult;
use App\Service\Fiscal\Provider\FiscalProviderResolver;
use App\Service\Fiscal\Provider\PseResponseFormatter;
use App\Service\Fiscal\Observability\FiscalAuditService;
use App\Entity\FiscalAuditLog;
use Doctrine\ORM\EntityManagerInterface;
use Greenter\Model\DocumentInterface;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

/**
 * Procesa emisión fiscal vía provider abstraction (SUNAT directo / PSE).
 * Soporta: factura/boleta, NC/ND, guía, resumen, baja.
 */
class FiscalEmitProcessor
{
    private EntityManagerInterface $em;
    private FiscalDocumentRepository $repo;
    private EmpresaRepository $empresaRepo;
    private SerializerInterface $serializer;
    private FiscalStorageService $storage;
    private FiscalWebhookService $webhook;
    private FiscalQueueService $queue;
    private FiscalProviderResolver $providerResolver;
    private FiscalPdfService $pdfService;
    private ?FiscalDocumentPdfResolver $pdfResolver;
    private LoggerInterface $logger;
    private ?FiscalAuditService $audit;

    public function __construct(
        EntityManagerInterface $em,
        FiscalDocumentRepository $repo,
        EmpresaRepository $empresaRepo,
        SerializerInterface $serializer,
        FiscalStorageService $storage,
        FiscalWebhookService $webhook,
        FiscalQueueService $queue,
        FiscalProviderResolver $providerResolver,
        FiscalPdfService $pdfService,
        LoggerInterface $logger,
        ?FiscalAuditService $audit = null,
        ?FiscalDocumentPdfResolver $pdfResolver = null
    ) {
        $this->em = $em;
        $this->repo = $repo;
        $this->empresaRepo = $empresaRepo;
        $this->serializer = $serializer;
        $this->storage = $storage;
        $this->webhook = $webhook;
        $this->queue = $queue;
        $this->providerResolver = $providerResolver;
        $this->pdfService = $pdfService;
        $this->logger = $logger;
        $this->audit = $audit;
        $this->pdfResolver = $pdfResolver;
    }

    public function processByUuid(string $documentUuid): void
    {
        $doc = $this->repo->findOneBy(['documentUuid' => $documentUuid]);
        if ($doc === null) {
            throw new \InvalidArgumentException('Documento fiscal no encontrado: ' . $documentUuid);
        }
        $this->process($doc);
    }

    public function process(FiscalDocument $doc): void
    {
        if (in_array($doc->getStatus(), [
            FiscalDocument::STATUS_ACCEPTED,
            FiscalDocument::STATUS_SENDING,
            FiscalDocument::STATUS_SENT,
        ], true)) {
            return;
        }

        $doc->setStatus(FiscalDocument::STATUS_SENDING);
        $this->em->flush();

        $started = microtime(true);
        $attemptNum = $doc->getRetryCount() + 1;
        $providerName = '';
        $ruc = '';
        $documentClass = '';

        $this->auditSafe(function () use ($doc, $attemptNum): void {
            if ($this->audit === null) {
                return;
            }
            $this->audit->setRequestId($this->audit->generateRequestId());
            $this->audit->fromDocument($doc, 'fiscal_processing_started', FiscalAuditLog::STATUS_PROCESSING, [
                'attempt' => $attemptNum,
            ]);
        });

        try {
            [$documentClass, $greenterDoc] = $this->deserializeSnapshot($doc);
            $ruc = trim((string) $greenterDoc->getCompany()->getRuc());
            $empresa = $this->empresaRepo->find($ruc);
            if ($empresa === null || !$empresa->isEnabled()) {
                throw new EmpresaNoRegistradaException($ruc, 'Empresa fiscal deshabilitada o no registrada');
            }

            $doc->setSendMode($empresa->getSendMode());
            $doc->setProvider($empresa->getProvider());
            $doc->setSunatMode(strtolower(trim($empresa->getAmbiente())) === 'produccion' ? 'production' : 'beta');

            $providerName = $this->providerResolver->resolveName($doc, $empresa);
            $doc->setProvider($providerName);
            $doc->setProviderVersion('1.0');

            $this->auditSafe(function () use ($doc, $providerName, $ruc, $empresa, $attemptNum): void {
                if ($this->audit === null) {
                    return;
                }
                $this->audit->fromDocument($doc, 'fiscal_provider_selected', FiscalAuditLog::STATUS_PROCESSING, [
                    'provider' => $providerName,
                    'ruc' => $ruc,
                    'send_mode' => $empresa->getSendMode(),
                    'connection_type' => $empresa->getConnectionType(),
                    'attempt' => $attemptNum,
                ]);
            });

            $result = $this->providerResolver->emit($doc, $empresa, $documentClass, $greenterDoc);

            if ($this->handleTicketOnlyResult($doc, $documentClass, $result, $providerName, $attemptNum, $started, $empresa)) {
                return;
            }

            $signedXml = $result->signedXml ?? '';
            if ($signedXml === '') {
                if (!empty($result->pseResponse)) {
                    $this->handlePseBusinessResult($doc, $result, $providerName, $attemptNum, $started);
                    if ($doc->getFiscalFingerprint()) {
                        $this->queue->releaseClaim($doc->getFiscalFingerprint());
                    }
                    return;
                }
                throw new \RuntimeException('Emisión sin XML firmado');
            }

            if (($result->pdf === null || $result->pdf === '') && FiscalDocumentClassResolver::supportsPdf($documentClass)) {
                $result->pdf = $this->pdfService->render($documentClass, $greenterDoc, $signedXml);
            }

            $stored = $this->storage->store(
                $doc->getTenantSlug(),
                $doc->getDocumentType(),
                $doc->getSeries(),
                $doc->getNumber(),
                $result->unsignedXml,
                $signedXml,
                $result->cdrZip,
                $result->pdf
            );

            $doc->setSentAt(new \DateTimeImmutable());
            $doc->setHash($result->hash);
            $doc->setXmlUrl($stored['xml_url']);
            $doc->setUnsignedXmlUrl($stored['unsigned_xml_url']);
            $doc->setXmlSignedUrl($stored['xml_signed_url']);
            $doc->setCdrUrl($stored['cdr_url']);
            $doc->setPdfUrl($stored['pdf_url']);
            if (($doc->getPdfUrl() === null || $doc->getPdfUrl() === '') && $this->pdfResolver !== null) {
                try {
                    $this->pdfResolver->generate($doc, true);
                } catch (\Throwable $e) {
                    $this->logger->warning('fiscal_pdf_generate_after_emit_failed', [
                        'uuid' => $doc->getDocumentUuid(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            if ($result->ticket !== null && $result->ticket !== '') {
                $doc->setTicket($result->ticket);
            }
            $doc->setSunatCode($result->sunatCode);
            $doc->setSunatMessage($result->sunatMessage ?? $result->pseMessage);
            if (!empty($result->pseResponse)) {
                $doc->setPseResponseJson(json_encode($result->pseResponse, JSON_UNESCAPED_UNICODE) ?: null);
            } elseif (!empty($result->cdrNotes) || !empty($result->sunatResponse)) {
                $doc->setPseResponseJson(json_encode([
                    'cdr_notes' => $result->cdrNotes,
                    'sunat' => $result->sunatResponse,
                ], JSON_UNESCAPED_UNICODE) ?: null);
            }

            if ($result->isObserved()) {
                $doc->setStatus(FiscalDocument::STATUS_OBSERVED);
                $doc->setAcceptedAt(new \DateTimeImmutable());
            } elseif ($result->isAccepted()) {
                $doc->setStatus(FiscalDocument::STATUS_ACCEPTED);
                $doc->setAcceptedAt(new \DateTimeImmutable());
            } elseif ($result->rejected) {
                $doc->setStatus(FiscalDocument::STATUS_REJECTED);
                $doc->setRejectedAt(new \DateTimeImmutable());
            } else {
                $doc->setStatus(FiscalDocument::STATUS_ERROR);
            }

            $this->recordAttempt($doc, $attemptNum, $providerName, $doc->getStatus(), $result, null, $started);
            $this->em->flush();
            $durationMs = (int) round((microtime(true) - $started) * 1000);
            if (in_array($doc->getStatus(), [FiscalDocument::STATUS_ACCEPTED, FiscalDocument::STATUS_OBSERVED], true)) {
                $this->auditSafe(function () use ($doc, $attemptNum, $durationMs, $providerName, $ruc): void {
                    if ($this->audit === null) {
                        return;
                    }
                    $this->audit->fromDocument($doc, 'fiscal_emit_success', FiscalAuditLog::STATUS_SUCCESS, [
                        'attempt' => $attemptNum,
                        'duration_ms' => $durationMs,
                        'provider' => $providerName,
                        'ruc' => $ruc,
                    ]);
                });
            } elseif (in_array($doc->getStatus(), [FiscalDocument::STATUS_REJECTED, FiscalDocument::STATUS_ERROR], true)) {
                $this->auditSafe(function () use ($doc, $attemptNum, $durationMs, $result): void {
                    if ($this->audit === null) {
                        return;
                    }
                    $this->audit->fromDocument($doc, 'fiscal_emit_failed', FiscalAuditLog::STATUS_FAILED, [
                        'attempt' => $attemptNum,
                        'duration_ms' => $durationMs,
                        'error_code' => $result->sunatCode,
                        'error_message' => $result->sunatMessage ?? $result->pseMessage,
                    ]);
                });
            }
            $this->notifyOrEnqueueSync($doc);

            if (in_array($doc->getStatus(), [FiscalDocument::STATUS_ACCEPTED, FiscalDocument::STATUS_OBSERVED], true) && $empresa->isEmailEnabled()) {
                $this->queue->push(FiscalQueueService::QUEUE_EMAIL, [
                    'document_uuid' => $doc->getDocumentUuid(),
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('fiscal_emit_failed', [
                'uuid' => $doc->getDocumentUuid(),
                'error' => $e->getMessage(),
            ]);
            $doc->setStatus(FiscalDocument::STATUS_ERROR);
            $doc->setSunatMessage($e->getMessage());
            $doc->setRetryCount($doc->getRetryCount() + 1);
            $durationMs = (int) round((microtime(true) - $started) * 1000);
            $this->recordAttempt($doc, $attemptNum, $providerName, FiscalDocument::STATUS_ERROR, null, $e->getMessage(), $started);
            $this->em->flush();

            $this->auditSafe(function () use ($doc, $attemptNum, $durationMs, $e, $providerName, $ruc): void {
                if ($this->audit === null) {
                    return;
                }
                $this->audit->fromDocument($doc, 'fiscal_emit_failed', FiscalAuditLog::STATUS_FAILED, [
                    'attempt' => $attemptNum,
                    'duration_ms' => $durationMs,
                    'provider' => $providerName,
                    'ruc' => $ruc,
                    'error_message' => $e->getMessage(),
                ]);
            });

            $empresa = null;
            if ($ruc !== '') {
                $empresa = $this->empresaRepo->find($ruc);
            }
            $nonRetryable = $this->isNonRetryableEmitError($e->getMessage());
            if ($empresa !== null && $empresa->isRetryEnabled() && !$nonRetryable && $doc->getRetryCount() < 5) {
                $delay = min(3600, 30 * (2 ** ($doc->getRetryCount() - 1)));
                $doc->setStatus(FiscalDocument::STATUS_RETRYING);
                $doc->setNextRetryAt((new \DateTimeImmutable())->modify('+' . $delay . ' seconds'));
                $this->em->flush();
                $this->auditSafe(function () use ($doc, $attemptNum, $delay): void {
                    if ($this->audit === null) {
                        return;
                    }
                    $this->audit->fromDocument($doc, 'fiscal_retry_scheduled', FiscalAuditLog::STATUS_RETRYING, [
                        'attempt' => $attemptNum,
                        'metadata_json' => json_encode(['delay_seconds' => $delay]),
                    ]);
                });
                $retryQueue = ($doc->getSendMode() === 'pse')
                    ? FiscalQueueService::QUEUE_PSE_RETRY
                    : FiscalQueueService::QUEUE_RETRY;
                $this->queue->scheduleRetry($doc->getDocumentUuid(), $delay, $retryQueue);
            } else {
                $this->notifyOrEnqueueSync($doc);
            }
            if ($doc->getFiscalFingerprint()) {
                $this->queue->releaseClaim($doc->getFiscalFingerprint());
            }
            throw $e;
        }

        if ($doc->getFiscalFingerprint()) {
            $this->queue->releaseClaim($doc->getFiscalFingerprint());
        }
    }

    private function handleTicketOnlyResult(
        FiscalDocument $doc,
        string $documentClass,
        FiscalEmitResult $result,
        string $providerName,
        int $attemptNum,
        float $started,
        Empresa $empresa
    ): bool {
        $signedXml = $result->signedXml ?? '';
        $ticket = $result->ticket ?? '';
        if ($signedXml !== '' || $ticket === '' || !FiscalDocumentClassResolver::isTicketBased($documentClass)) {
            return false;
        }

        $doc->setTicket($ticket);
        $doc->setSentAt(new \DateTimeImmutable());
        $doc->setSunatCode($result->sunatCode);
        $doc->setSunatMessage($result->sunatMessage);
        $doc->setStatus(FiscalDocument::STATUS_SENT);
        $this->recordAttempt($doc, $attemptNum, $providerName, FiscalDocument::STATUS_SENT, $result, null, $started);
        $this->em->flush();
        $this->notifyOrEnqueueSync($doc);
        $this->queue->scheduleRetry($doc->getDocumentUuid(), 30, FiscalQueueService::QUEUE_STATUS_POLL);
        if ($doc->getFiscalFingerprint()) {
            $this->queue->releaseClaim($doc->getFiscalFingerprint());
        }
        return true;
    }

    /**
     * PSE respondió (HTTP 200/4xx con JSON) pero sin XML firmado — persistir respuesta y mensaje de negocio.
     */
    private function handlePseBusinessResult(
        FiscalDocument $doc,
        FiscalEmitResult $result,
        string $providerName,
        int $attemptNum,
        float $started
    ): void {
        $pseMessage = $result->pseMessage ?: PseResponseFormatter::message($result->pseResponse);
        $displayMessage = $pseMessage !== ''
            ? $pseMessage
            : ($result->sunatMessage ?? 'Rechazado por PSE');

        $doc->setSentAt(new \DateTimeImmutable());
        $doc->setHash($result->hash !== '' ? $result->hash : null);
        $doc->setSunatCode($result->sunatCode);
        $doc->setSunatMessage($displayMessage);
        if (!empty($result->pseResponse)) {
            $doc->setPseResponseJson(json_encode($result->pseResponse, JSON_UNESCAPED_UNICODE) ?: null);
        }

        if ($result->rejected || !$result->success) {
            $doc->setStatus(FiscalDocument::STATUS_REJECTED);
            $doc->setRejectedAt(new \DateTimeImmutable());
        } else {
            $doc->setStatus(FiscalDocument::STATUS_ERROR);
        }

        $this->recordAttempt($doc, $attemptNum, $providerName, $doc->getStatus(), $result, null, $started);
        $this->em->flush();

        $durationMs = (int) round((microtime(true) - $started) * 1000);
        $this->auditSafe(function () use ($doc, $attemptNum, $durationMs, $result, $displayMessage): void {
            if ($this->audit === null) {
                return;
            }
            $this->audit->fromDocument($doc, 'fiscal_emit_failed', FiscalAuditLog::STATUS_FAILED, [
                'attempt' => $attemptNum,
                'duration_ms' => $durationMs,
                'error_code' => $result->sunatCode,
                'error_message' => $displayMessage,
                'metadata_json' => json_encode([
                    'pse_response' => PseResponseFormatter::summarize($result->pseResponse),
                ], JSON_UNESCAPED_UNICODE) ?: null,
            ]);
        });

        $this->notifyOrEnqueueSync($doc);
    }

    /**
     * @return array{0: string, 1: DocumentInterface}
     */
    private function deserializeSnapshot(FiscalDocument $doc): array
    {
        $raw = $doc->getSnapshotJson();
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException('snapshot_json inválido');
        }
        if (isset($data['document']) && is_array($data['document'])) {
            $data = $data['document'];
        }

        $class = FiscalDocumentClassResolver::resolve($data, $doc);
        $greenterDoc = $this->serializer->deserialize(json_encode($data), $class, 'json');
        return [$class, $greenterDoc];
    }

    private function recordAttempt(
        FiscalDocument $doc,
        int $attemptNum,
        string $provider,
        string $status,
        ?FiscalEmitResult $result,
        ?string $error,
        float $started
    ): void {
        $attempt = new FiscalEmitAttempt();
        $attempt->setDocumentUuid($doc->getDocumentUuid());
        $attempt->setAttemptNumber($attemptNum);
        $attempt->setProvider($provider ?: null);
        $attempt->setStatus($status);
        $attempt->setDurationMs((int) round((microtime(true) - $started) * 1000));
        if ($result !== null) {
            $attempt->setSunatCode($result->sunatCode);
            $attempt->setSunatMessage($result->sunatMessage);
            $attempt->setPseMessage($result->pseMessage);
        }
        if ($error !== null) {
            $attempt->setErrorMessage($error);
        }
        $this->em->persist($attempt);
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

    private function auditSafe(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable) {
        }
    }

    /** Errores de certificado/firma no se resuelven reintentando. */
    private function isNonRetryableEmitError(string $message): bool
    {
        $m = strtolower($message);
        foreach ([
            'openssl_sign',
            'openssl_pkey_get_private',
            'private key',
            'cannot be coerced',
            'certificado inválido: ',
            'certificado inválido',
            'clave privada',
        ] as $needle) {
            if (str_contains($m, $needle)) {
                return true;
            }
        }

        return false;
    }
}

