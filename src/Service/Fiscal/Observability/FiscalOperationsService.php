<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Observability;

use App\Entity\FiscalAuditLog;
use App\Entity\FiscalDocument;
use App\Repository\EmpresaRepository;
use App\Repository\FiscalAuditLogRepository;
use App\Repository\FiscalDocumentRepository;
use App\Service\Fiscal\FiscalDocumentDetailService;
use App\Service\Fiscal\FiscalQueueService;
use App\Service\Fiscal\Provider\PseResponseFormatter;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Agregaciones para dashboard Operaciones Fiscales.
 */
class FiscalOperationsService
{
    private FiscalDocumentRepository $documents;
    private FiscalAuditLogRepository $auditLogs;
    private EmpresaRepository $empresas;
    private FiscalDocumentDetailService $detailService;
    private FiscalQueueService $queue;
    private EntityManagerInterface $em;
    private FiscalAlertService $alertService;

    public function __construct(
        FiscalDocumentRepository $documents,
        FiscalAuditLogRepository $auditLogs,
        EmpresaRepository $empresas,
        FiscalDocumentDetailService $detailService,
        FiscalQueueService $queue,
        EntityManagerInterface $em,
        FiscalAlertService $alertService
    ) {
        $this->documents = $documents;
        $this->auditLogs = $auditLogs;
        $this->empresas = $empresas;
        $this->detailService = $detailService;
        $this->queue = $queue;
        $this->em = $em;
        $this->alertService = $alertService;
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $this->alertService->runDetection();

        $today = new \DateTimeImmutable('today midnight');
        $stats = $this->detailService->globalStats(null, $today, null);
        $auditToday = $this->auditLogs->globalSummarySince($today);

        $errorsToday = (int) ($auditToday['failures'] ?? 0);
        $retriesToday = (int) ($auditToday['retries'] ?? 0);
        $avgDuration = isset($auditToday['avg_duration_ms']) ? (int) round((float) $auditToday['avg_duration_ms']) : null;

        $connectedTenants = (int) $this->em->getConnection()->fetchOne(
            'SELECT COUNT(DISTINCT tenant_slug) FROM empresa WHERE enabled = 1 AND connection_status = \'connected\' AND tenant_slug IS NOT NULL'
        );
        $tenantsWithError = (int) $this->em->getConnection()->fetchOne(
            'SELECT COUNT(DISTINCT tenant_slug) FROM empresa WHERE enabled = 1 AND connection_status NOT IN (\'connected\',\'testing\') AND tenant_slug IS NOT NULL'
        );

        return [
            'cards' => [
                'documents_today' => $stats['documents_today'] ?? 0,
                'pending' => $stats['pending'] ?? 0,
                'errors_today' => $errorsToday,
                'retries_today' => $retriesToday,
                'avg_duration_ms' => $avgDuration,
                'tenants_connected' => $connectedTenants,
                'tenants_with_error' => $tenantsWithError,
                'open_alerts' => $this->alertService->countOpen(),
            ],
            'charts' => [
                'emissions_by_hour' => $this->auditLogs->emissionsByHourSince($today),
                'errors_by_provider' => $this->auditLogs->errorsByProviderSince($today),
                'avg_duration_by_provider' => $this->avgDurationByProvider($today),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function tenantsTable(array $filters = []): array
    {
        $auditRows = $this->auditLogs->tenantOperationsSummary(24);
        $auditBySlug = [];
        foreach ($auditRows as $row) {
            $auditBySlug[(string) $row['tenant_slug']] = $row;
        }

        $pendingBySlug = $this->pendingCountByTenant();
        $lastEmitBySlug = $this->lastEmitByTenant();

        $items = [];
        foreach ($this->empresas->findBy(['enabled' => true], ['tenantSlug' => 'ASC']) as $empresa) {
            $slug = $empresa->getTenantSlug();
            if ($slug === null || $slug === '') {
                continue;
            }

            if (!empty($filters['tenant_slug']) && $slug !== $filters['tenant_slug']) {
                continue;
            }
            if (!empty($filters['provider']) && ($empresa->getProvider() ?? '') !== $filters['provider']) {
                continue;
            }
            if (!empty($filters['send_mode']) && $empresa->getSendMode() !== $filters['send_mode']) {
                continue;
            }
            if (!empty($filters['connection_status']) && $empresa->getConnectionStatus() !== $filters['connection_status']) {
                continue;
            }

            $audit = $auditBySlug[$slug] ?? [];
            $pending = $pendingBySlug[$slug] ?? 0;
            $errors24h = (int) ($audit['errors_24h'] ?? 0);

            if (!empty($filters['errors_only']) && $errors24h <= 0 && $empresa->getConnectionStatus() === 'connected') {
                continue;
            }
            if (!empty($filters['pending_only']) && $pending <= 0) {
                continue;
            }

            $items[] = [
                'tenant_id' => $empresa->getTenantId(),
                'tenant_slug' => $slug,
                'empresa' => $slug,
                'ruc' => $empresa->getRuc(),
                'send_mode' => $empresa->getSendMode(),
                'provider' => $empresa->getProvider(),
                'connection_status' => $empresa->getConnectionStatus(),
                'connection_error' => $empresa->getConnectionError(),
                'pending' => $pending,
                'last_emit_at' => $lastEmitBySlug[$slug] ?? null,
                'last_error' => $audit['last_error'] ?? $empresa->getConnectionError(),
                'retries_24h' => (int) ($audit['retries_24h'] ?? 0),
                'errors_24h' => $errors24h,
                'avg_duration_ms' => isset($audit['avg_duration_ms']) ? (int) round((float) $audit['avg_duration_ms']) : null,
            ];
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $lq = mb_strtolower($q);
            $items = array_values(array_filter($items, static function (array $row) use ($lq): bool {
                foreach (['tenant_slug', 'ruc', 'provider', 'send_mode', 'last_error'] as $k) {
                    if (isset($row[$k]) && str_contains(mb_strtolower((string) $row[$k]), $lq)) {
                        return true;
                    }
                }
                return false;
            }));
        }

        return ['items' => $items, 'total' => count($items)];
    }

    /**
     * @return array<string, mixed>
     */
    public function queueMonitor(): array
    {
        $groups = [
            'queued' => [FiscalDocument::STATUS_QUEUED, FiscalDocument::STATUS_PENDING],
            'processing' => [FiscalDocument::STATUS_SENDING],
            'failed' => [FiscalDocument::STATUS_ERROR, FiscalDocument::STATUS_REJECTED],
            'retrying' => [FiscalDocument::STATUS_RETRYING],
        ];

        $result = [];
        foreach ($groups as $key => $statuses) {
            $docs = $this->documents->findByStatuses($statuses, 50);
            $result[$key] = array_map([$this, 'serializeQueueItem'], $docs);
            $result[$key . '_count'] = $this->documents->countByStatuses($statuses);
        }

        $result['redis'] = [
            'emit_queue' => $this->queue->isReachable() ? $this->queue->queueLength(FiscalQueueService::QUEUE_EMIT) : 0,
            'retry_scheduled' => $this->queue->isReachable()
                ? $this->queue->scheduledRetryCount(FiscalQueueService::QUEUE_RETRY)
                    + $this->queue->scheduledRetryCount(FiscalQueueService::QUEUE_PSE_RETRY)
                : 0,
            'redis_connected' => $this->queue->isReachable(),
        ];

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function auditTimeline(string $uuid): array
    {
        $doc = $this->detailService->findDocument($uuid);
        if ($doc === null) {
            throw new \InvalidArgumentException('Documento no encontrado');
        }

        $logs = $this->auditLogs->findByDocumentUuid($uuid);
        $timeline = [];
        foreach ($logs as $log) {
            $timeline[] = [
                'event_type' => $log->getEventType(),
                'status' => $log->getStatus(),
                'at' => $log->getCreatedAt()->format(DATE_ATOM),
                'provider' => $log->getProvider(),
                'attempt' => $log->getAttempt(),
                'duration_ms' => $log->getDurationMs(),
                'error_code' => $log->getErrorCode(),
                'error_message' => $log->getErrorMessage(),
                'metadata_json' => $log->getMetadataJson(),
                'request_id' => $log->getRequestId(),
            ];
        }

        return [
            'document_uuid' => $uuid,
            'tenant_slug' => $doc->getTenantSlug(),
            'tenant_id' => $doc->getTenantId(),
            'series' => $doc->getSeries(),
            'number' => $doc->getNumber(),
            'document_type' => $doc->getDocumentType(),
            'retry_count' => $doc->getRetryCount(),
            'timeline' => $timeline,
            'merged_timeline' => $this->detailService->buildDetail($doc)['timeline'] ?? [],
        ];
    }

    public function cancelDocument(string $uuid, FiscalAuditService $audit): void
    {
        $doc = $this->documents->findOneBy(['documentUuid' => $uuid]);
        if ($doc === null) {
            throw new \InvalidArgumentException('Documento no encontrado');
        }
        if (!in_array($doc->getStatus(), [
            FiscalDocument::STATUS_QUEUED,
            FiscalDocument::STATUS_PENDING,
            FiscalDocument::STATUS_RETRYING,
        ], true)) {
            throw new \InvalidArgumentException('Solo se puede cancelar documentos en cola o reintento');
        }

        $doc->setStatus(FiscalDocument::STATUS_CANCELLED);
        $this->em->flush();

        try {
            $audit->fromDocument($doc, 'fiscal_document_cancelled', FiscalAuditLog::STATUS_CANCELLED);
        } catch (\Throwable) {
        }
    }

    /**
     * @return array<string, int>
     */
    private function pendingCountByTenant(): array
    {
        $conn = $this->em->getConnection();
        $rows = $conn->fetchAllAssociative(
            'SELECT tenant_slug, COUNT(*) AS c FROM fiscal_documents WHERE status IN (\'pending\',\'queued\',\'retrying\') AND tenant_slug IS NOT NULL GROUP BY tenant_slug'
        );
        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row['tenant_slug']] = (int) $row['c'];
        }
        return $out;
    }

    /**
     * @return array<string, string>
     */
    private function lastEmitByTenant(): array
    {
        $conn = $this->em->getConnection();
        $rows = $conn->fetchAllAssociative(
            'SELECT tenant_slug, MAX(COALESCE(accepted_at, sent_at, updated_at)) AS last_emit FROM fiscal_documents WHERE tenant_slug IS NOT NULL GROUP BY tenant_slug'
        );
        $out = [];
        foreach ($rows as $row) {
            if ($row['last_emit'] !== null) {
                $out[(string) $row['tenant_slug']] = (string) $row['last_emit'];
            }
        }
        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function avgDurationByProvider(\DateTimeInterface $since): array
    {
        $conn = $this->em->getConnection();
        return $conn->fetchAllAssociative(
            'SELECT provider, AVG(duration_ms) AS avg_ms, COUNT(*) AS samples FROM fiscal_audit_logs WHERE provider IS NOT NULL AND duration_ms IS NOT NULL AND created_at >= :since GROUP BY provider ORDER BY avg_ms DESC',
            ['since' => $since->format('Y-m-d H:i:s')]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeQueueItem(FiscalDocument $doc): array
    {
        $pseRaw = null;
        if ($doc->getPseResponseJson()) {
            $decoded = json_decode($doc->getPseResponseJson(), true);
            $pseRaw = is_array($decoded) ? $decoded : null;
        }
        $pseSummary = PseResponseFormatter::summarize($pseRaw);
        $pseMessage = $pseSummary['mensaje'] ?? null;
        $sunatMessage = $doc->getSunatMessage();
        $displayMessage = is_string($pseMessage) && $pseMessage !== ''
            ? $pseMessage
            : $sunatMessage;

        return [
            'document_uuid' => $doc->getDocumentUuid(),
            'tenant_slug' => $doc->getTenantSlug(),
            'document_type' => $doc->getDocumentType(),
            'series' => $doc->getSeries(),
            'number' => $doc->getNumber(),
            'status' => $doc->getStatus(),
            'provider' => $doc->getProvider(),
            'send_mode' => $doc->getSendMode(),
            'retry_count' => $doc->getRetryCount(),
            'sunat_message' => $sunatMessage,
            'pse_message' => $pseMessage,
            'pse_response' => $pseSummary,
            'display_message' => $displayMessage,
            'queued_at' => $doc->getQueuedAt() ? $doc->getQueuedAt()->format(DATE_ATOM) : null,
            'next_retry_at' => $doc->getNextRetryAt() ? $doc->getNextRetryAt()->format(DATE_ATOM) : null,
            'created_at' => $doc->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}
