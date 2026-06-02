<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use App\Entity\FiscalAuditLog;
use App\Entity\FiscalDocument;
use App\Entity\FiscalEmitAttempt;
use App\Entity\FiscalWebhookEvent;
use App\Entity\OutboundEmailLog;
use App\Repository\FiscalAuditLogRepository;
use App\Repository\FiscalDocumentRepository;
use App\Repository\FiscalEmitAttemptRepository;
use App\Repository\FiscalWebhookEventRepository;
use App\Repository\OutboundEmailLogRepository;

/**
 * Detalle fiscal enriquecido + timeline para dashboard/API.
 */
class FiscalDocumentDetailService
{
    private FiscalDocumentRepository $documents;
    private FiscalEmitAttemptRepository $attempts;
    private OutboundEmailLogRepository $emails;
    private FiscalWebhookEventRepository $webhooks;
    private FiscalAuditLogRepository $auditLogs;
    private ?FiscalDocumentPdfResolver $pdfResolver;

    public function __construct(
        FiscalDocumentRepository $documents,
        FiscalEmitAttemptRepository $attempts,
        OutboundEmailLogRepository $emails,
        FiscalWebhookEventRepository $webhooks,
        FiscalAuditLogRepository $auditLogs,
        ?FiscalDocumentPdfResolver $pdfResolver = null
    ) {
        $this->documents = $documents;
        $this->attempts = $attempts;
        $this->emails = $emails;
        $this->webhooks = $webhooks;
        $this->auditLogs = $auditLogs;
        $this->pdfResolver = $pdfResolver;
    }

    public function findDocument(string $uuid): ?FiscalDocument
    {
        return $this->documents->findOneBy(['documentUuid' => $uuid]);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildDetail(FiscalDocument $doc): array
    {
        $attemptRows = $this->attempts->findByDocumentUuid($doc->getDocumentUuid());
        $emailRows = $this->emails->findByDocumentUuid($doc->getDocumentUuid());
        $webhookRows = $this->webhooks->findByDocumentUuid($doc->getDocumentUuid());
        $auditRows = $this->auditLogs->findByDocumentUuid($doc->getDocumentUuid());

        $snapshot = json_decode($doc->getSnapshotJson(), true);
        $pseResponse = null;
        if ($doc->getPseResponseJson()) {
            $pseResponse = json_decode($doc->getPseResponseJson(), true);
        }

        return [
            'document' => $this->serializeDocument($doc),
            'snapshot_json' => is_array($snapshot) ? $snapshot : null,
            'pse_response' => $pseResponse,
            'attempts' => array_map([$this, 'serializeAttempt'], $attemptRows),
            'email_logs' => array_map([$this, 'serializeEmail'], $emailRows),
            'webhook_events' => array_map([$this, 'serializeWebhook'], $webhookRows),
            'timeline' => $this->buildTimeline($doc, $attemptRows, $emailRows, $webhookRows, $auditRows),
            'download_urls' => [
                'xml' => '/api/v1/fiscal/documents/' . $doc->getDocumentUuid() . '/download/xml',
                'signed_xml' => '/api/v1/fiscal/documents/' . $doc->getDocumentUuid() . '/download/signed_xml',
                'cdr' => '/api/v1/fiscal/documents/' . $doc->getDocumentUuid() . '/download/cdr',
                'pdf' => '/api/v1/fiscal/documents/' . $doc->getDocumentUuid() . '/download/pdf',
                'unsigned_xml' => '/api/v1/fiscal/documents/' . $doc->getDocumentUuid() . '/download/unsigned_xml',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function globalStats(
        ?string $tenantSlug = null,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null,
        ?int $tenantId = null
    ): array {
        $counts = $this->documents->countByStatus($tenantSlug, $from, $to);
        $total = array_sum($counts);
        $errors = ($counts['error'] ?? 0) + ($counts['rejected'] ?? 0);
        $pending = ($counts['pending'] ?? 0) + ($counts['queued'] ?? 0);
        $processing = ($counts['sending'] ?? 0) + ($counts['retrying'] ?? 0);
        $sent = $counts['sent'] ?? 0;
        $accepted = $counts['accepted'] ?? 0;

        $todayFrom = new \DateTimeImmutable('today midnight', new \DateTimeZone(FiscalDateRangeParser::TZ));
        $todayFilters = [
            'from' => $todayFrom,
            'to' => $todayFrom->modify('+1 day'),
            'electronic_only' => true,
        ];
        if ($tenantSlug !== null && $tenantSlug !== '') {
            $todayFilters['tenant_slug'] = $tenantSlug;
        }
        if ($tenantId !== null && $tenantId > 0) {
            $todayFilters['tenant_id'] = $tenantId;
        }

        return [
            'total' => $total,
            'documents_today' => $this->documents->countByFilters($todayFilters),
            'pending' => $pending,
            'in_queue' => $counts['queued'] ?? 0,
            'processing' => $processing,
            'sent' => $sent,
            'accepted' => $accepted,
            'rejected' => $counts['rejected'] ?? 0,
            'errors' => $errors,
            'retries' => $counts['retrying'] ?? 0,
            'emails_pending' => $this->emails->countPending($tenantSlug),
            'by_status' => $counts,
            'tenants' => $this->documents->countByTenant($tenantSlug),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDocument(FiscalDocument $doc): array
    {
        return [
            'document_uuid' => $doc->getDocumentUuid(),
            'tenant_id' => $doc->getTenantId(),
            'tenant_slug' => $doc->getTenantSlug(),
            'sale_id' => $doc->getSaleId(),
            'document_type' => $doc->getDocumentType(),
            'series' => $doc->getSeries(),
            'number' => $doc->getNumber(),
            'status' => $doc->getStatus(),
            'send_mode' => $doc->getSendMode(),
            'sunat_mode' => $doc->getSunatMode(),
            'provider' => $doc->getProvider(),
            'fiscal_fingerprint' => $doc->getFiscalFingerprint(),
            'snapshot_version' => $doc->getSnapshotVersion(),
            'schema_version' => $doc->getSchemaVersion(),
            'greenter_version' => $doc->getGreenterVersion(),
            'provider_version' => $doc->getProviderVersion(),
            'customer_email' => $doc->getCustomerEmail(),
            'queued_at' => $this->fmt($doc->getQueuedAt()),
            'sent_at' => $this->fmt($doc->getSentAt()),
            'accepted_at' => $this->fmt($doc->getAcceptedAt()),
            'rejected_at' => $this->fmt($doc->getRejectedAt()),
            'next_retry_at' => $this->fmt($doc->getNextRetryAt()),
            'xml_url' => $doc->getXmlUrl(),
            'xml_signed_url' => $doc->getXmlSignedUrl(),
            'unsigned_xml_url' => $doc->getUnsignedXmlUrl(),
            'cdr_url' => $doc->getCdrUrl(),
            'pdf_url' => $doc->getPdfUrl(),
            'pdf_available' => $this->pdfResolver !== null && $this->pdfResolver->hasStoredPdf($doc),
            'pdf_can_generate' => $this->pdfResolver !== null && $this->pdfResolver->canGenerate($doc),
            'hash' => $doc->getHash(),
            'ticket' => $doc->getTicket(),
            'sunat_code' => $doc->getSunatCode(),
            'sunat_message' => $doc->getSunatMessage(),
            'retry_count' => $doc->getRetryCount(),
            'email_status' => $doc->getEmailStatus(),
            'created_at' => $doc->getCreatedAt()->format(DATE_ATOM),
            'updated_at' => $doc->getUpdatedAt()->format(DATE_ATOM),
        ];
    }

    /**
     * @param FiscalEmitAttempt[] $attempts
     * @param OutboundEmailLog[] $emails
     * @param FiscalWebhookEvent[] $webhooks
     * @param FiscalAuditLog[] $auditRows
     * @return array<int, array<string, mixed>>
     */
    private function buildTimeline(
        FiscalDocument $doc,
        array $attempts,
        array $emails,
        array $webhooks,
        array $auditRows = []
    ): array {
        $events = [];
        $push = static function (array &$events, string $type, ?\DateTimeInterface $at, array $meta = []): void {
            if ($at === null) {
                return;
            }
            $events[] = array_merge([
                'type' => $type,
                'at' => $at->format(DATE_ATOM),
                'ts' => $at->getTimestamp(),
            ], $meta);
        };

        $push($events, 'created', $doc->getCreatedAt());
        $push($events, 'queued', $doc->getQueuedAt());
        $push($events, 'sent', $doc->getSentAt(), ['ticket' => $doc->getTicket()]);
        $push($events, 'accepted', $doc->getAcceptedAt(), ['sunat_code' => $doc->getSunatCode()]);
        $push($events, 'rejected', $doc->getRejectedAt(), ['sunat_message' => $doc->getSunatMessage()]);

        foreach ($attempts as $a) {
            $events[] = [
                'type' => 'emit_attempt',
                'at' => $a->getCreatedAt()->format(DATE_ATOM),
                'ts' => $a->getCreatedAt()->getTimestamp(),
                'attempt' => $a->getAttemptNumber(),
                'status' => $a->getStatus(),
                'provider' => $a->getProvider(),
                'sunat_code' => $a->getSunatCode(),
                'sunat_message' => $a->getSunatMessage(),
                'pse_message' => $a->getPseMessage(),
                'error' => $a->getErrorMessage(),
                'duration_ms' => $a->getDurationMs(),
            ];
        }
        foreach ($emails as $e) {
            $events[] = [
                'type' => 'email_' . $e->getStatus(),
                'at' => ($e->getSentAt() ?? $e->getCreatedAt())->format(DATE_ATOM),
                'ts' => ($e->getSentAt() ?? $e->getCreatedAt())->getTimestamp(),
                'recipient' => $e->getRecipientEmail(),
                'attempts' => $e->getAttempts(),
                'error' => $e->getErrorMessage(),
            ];
        }
        foreach ($webhooks as $w) {
            $events[] = [
                'type' => 'webhook_' . ($w->getDeliveredAt() ? 'delivered' : 'failed'),
                'at' => ($w->getDeliveredAt() ?? $w->getCreatedAt())->format(DATE_ATOM),
                'ts' => ($w->getDeliveredAt() ?? $w->getCreatedAt())->getTimestamp(),
                'http_status' => $w->getHttpStatus(),
                'event_key' => $w->getEventKey(),
            ];
        }
        foreach ($auditRows as $a) {
            $events[] = [
                'type' => $a->getEventType(),
                'at' => $a->getCreatedAt()->format(DATE_ATOM),
                'ts' => $a->getCreatedAt()->getTimestamp(),
                'status' => $a->getStatus(),
                'provider' => $a->getProvider(),
                'attempt' => $a->getAttempt(),
                'duration_ms' => $a->getDurationMs(),
                'error' => $a->getErrorMessage(),
                'request_id' => $a->getRequestId(),
            ];
        }

        usort($events, static fn ($a, $b) => ($a['ts'] ?? 0) <=> ($b['ts'] ?? 0));
        return $events;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAttempt(FiscalEmitAttempt $a): array
    {
        return [
            'attempt_number' => $a->getAttemptNumber(),
            'provider' => $a->getProvider(),
            'status' => $a->getStatus(),
            'sunat_code' => $a->getSunatCode(),
            'sunat_message' => $a->getSunatMessage(),
            'pse_message' => $a->getPseMessage(),
            'error_message' => $a->getErrorMessage(),
            'duration_ms' => $a->getDurationMs(),
            'created_at' => $a->getCreatedAt()->format(DATE_ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeEmail(OutboundEmailLog $e): array
    {
        return [
            'recipient_email' => $e->getRecipientEmail(),
            'status' => $e->getStatus(),
            'attempts' => $e->getAttempts(),
            'error_message' => $e->getErrorMessage(),
            'provider_response' => $e->getProviderResponse(),
            'sent_at' => $this->fmt($e->getSentAt()),
            'created_at' => $e->getCreatedAt()->format(DATE_ATOM),
            'updated_at' => $e->getUpdatedAt()->format(DATE_ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeWebhook(FiscalWebhookEvent $w): array
    {
        return [
            'event_key' => $w->getEventKey(),
            'http_status' => $w->getHttpStatus(),
            'delivered_at' => $this->fmt($w->getDeliveredAt()),
            'created_at' => $w->getCreatedAt()->format(DATE_ATOM),
            'response_preview' => $w->getResponseBody() ? substr($w->getResponseBody(), 0, 500) : null,
        ];
    }

    private function fmt(?\DateTimeInterface $dt): ?string
    {
        return $dt ? $dt->format(DATE_ATOM) : null;
    }
}
