<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Observability;

use App\Entity\FiscalAuditLog;
use App\Entity\FiscalDocument;
use App\Entity\Empresa;
use App\Service\Fiscal\FiscalQueueService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Persistencia async de audit vía cola Redis (no bloquea emisión).
 */
class FiscalAuditService
{
    private EntityManagerInterface $em;
    private FiscalStructuredLogger $structuredLogger;
    private FiscalSecretRedactor $redactor;
    private ?FiscalQueueService $queue;
    private ?FiscalMetricsService $metrics;

    /** @var string|null request_id por request HTTP/worker */
    private ?string $requestId = null;

    public function __construct(
        EntityManagerInterface $em,
        FiscalStructuredLogger $structuredLogger,
        FiscalSecretRedactor $redactor,
        ?FiscalQueueService $queue = null,
        ?FiscalMetricsService $metrics = null
    ) {
        $this->em = $em;
        $this->structuredLogger = $structuredLogger;
        $this->redactor = $redactor;
        $this->queue = $queue;
        $this->metrics = $metrics;
    }

    public function setRequestId(?string $requestId): void
    {
        $this->requestId = $requestId;
    }

    public function generateRequestId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function record(string $eventType, string $status, array $extra = []): void
    {
        $entry = array_merge([
            'event_type' => $eventType,
            'status' => $status,
            'request_id' => $extra['request_id'] ?? $this->requestId,
            'created_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ], $extra);

        $entry = $this->redactor->redactArray($entry);

        $this->structuredLogger->log($eventType, $entry);

        if ($this->queue !== null && $this->queue->isEnabled()) {
            $this->queue->pushAudit(json_encode($entry, JSON_UNESCAPED_UNICODE) ?: '{}');
            return;
        }
        $this->persistEntry($entry);
    }

    public function fromDocument(FiscalDocument $doc, string $eventType, string $status, array $extra = []): void
    {
        $this->record($eventType, $status, array_merge([
            'tenant_id' => $doc->getTenantId(),
            'tenant_slug' => $doc->getTenantSlug(),
            'document_uuid' => $doc->getDocumentUuid(),
            'document_type' => $doc->getDocumentType(),
            'series' => $doc->getSeries(),
            'number' => $doc->getNumber(),
            'sale_id' => $doc->getSaleId(),
            'send_mode' => $doc->getSendMode(),
            'provider' => $doc->getProvider(),
            'attempt' => $extra['attempt'] ?? ($doc->getRetryCount() + 1),
        ], $extra));
    }

    public function fromEmpresa(Empresa $empresa, string $eventType, string $status, array $extra = []): void
    {
        $this->record($eventType, $status, array_merge([
            'tenant_id' => $empresa->getTenantId(),
            'tenant_slug' => $empresa->getTenantSlug(),
            'ruc' => $empresa->getRuc(),
            'send_mode' => $empresa->getSendMode(),
            'provider' => $empresa->getProvider(),
            'connection_type' => $empresa->getConnectionType(),
        ], $extra));
    }

    /**
     * @param array<string, mixed> $entry
     */
    public function persistEntry(array $entry): void
    {
        $log = new FiscalAuditLog();
        $log->setEventType((string) ($entry['event_type'] ?? 'unknown'));
        $log->setStatus((string) ($entry['status'] ?? 'processing'));
        if (isset($entry['tenant_id'])) {
            $log->setTenantId((int) $entry['tenant_id']);
        }
        if (!empty($entry['tenant_slug'])) {
            $log->setTenantSlug((string) $entry['tenant_slug']);
        }
        if (!empty($entry['ruc'])) {
            $log->setRuc((string) $entry['ruc']);
        }
        if (!empty($entry['document_uuid'])) {
            $log->setDocumentUuid((string) $entry['document_uuid']);
        }
        if (!empty($entry['document_type'])) {
            $log->setDocumentType((string) $entry['document_type']);
        }
        if (!empty($entry['series'])) {
            $log->setSeries((string) $entry['series']);
        }
        if (!empty($entry['number'])) {
            $log->setNumber((string) $entry['number']);
        }
        if (isset($entry['sale_id'])) {
            $log->setSaleId((int) $entry['sale_id']);
        }
        if (!empty($entry['send_mode'])) {
            $log->setSendMode((string) $entry['send_mode']);
        }
        if (!empty($entry['provider'])) {
            $log->setProvider((string) $entry['provider']);
        }
        if (!empty($entry['connection_type'])) {
            $log->setConnectionType((string) $entry['connection_type']);
        }
        if (isset($entry['attempt'])) {
            $log->setAttempt((int) $entry['attempt']);
        }
        if (!empty($entry['request_id'])) {
            $log->setRequestId((string) $entry['request_id']);
        }
        if (!empty($entry['queue_job_id'])) {
            $log->setQueueJobId((string) $entry['queue_job_id']);
        }
        if (!empty($entry['error_code'])) {
            $log->setErrorCode((string) $entry['error_code']);
        }
        if (!empty($entry['error_message'])) {
            $log->setErrorMessage((string) $entry['error_message']);
        }
        if (!empty($entry['error_stack'])) {
            $log->setErrorStack((string) $entry['error_stack']);
        }
        if (isset($entry['duration_ms'])) {
            $log->setDurationMs((int) $entry['duration_ms']);
        }
        if (!empty($entry['metadata_json'])) {
            $log->setMetadataJson(is_string($entry['metadata_json']) ? $entry['metadata_json'] : json_encode($entry['metadata_json']));
        }
        if (!empty($entry['started_at'])) {
            $log->setStartedAt(new \DateTimeImmutable((string) $entry['started_at']));
        }
        if (!empty($entry['finished_at'])) {
            $log->setFinishedAt(new \DateTimeImmutable((string) $entry['finished_at']));
        }

        try {
            $this->em->persist($log);
            $this->em->flush();
            if ($this->metrics !== null) {
                $this->metrics->recordFromAuditEntry($entry);
            }
        } catch (\Throwable) {
            // Nunca fallar emisión por audit
        }
    }

    /**
     * Drena cola Redis audit (llamar desde worker).
     */
    public function drainQueue(int $max = 100): int
    {
        if ($this->queue === null || !$this->queue->isEnabled()) {
            return 0;
        }
        $count = 0;
        for ($i = 0; $i < $max; $i++) {
            $raw = $this->queue->popAuditRaw();
            if ($raw === null || $raw === '') {
                break;
            }
            $entry = json_decode($raw, true);
            if (is_array($entry)) {
                $this->persistEntry($entry);
                $count++;
            }
        }
        return $count;
    }
}
