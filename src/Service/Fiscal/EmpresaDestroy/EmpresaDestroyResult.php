<?php

declare(strict_types=1);

namespace App\Service\Fiscal\EmpresaDestroy;

/**
 * Resumen de eliminación completa de empresa fiscal.
 */
final class EmpresaDestroyResult
{
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_FAILED = 'failed';

    public function __construct(
        public readonly string $ruc,
        public readonly ?string $tenantSlug,
        public readonly ?int $tenantId,
        public readonly bool $alreadyDeleted,
        public readonly string $cleanupStatus,
        public readonly bool $databasePurged,
        public readonly bool $redisPurged,
        public readonly bool $storagePurged,
        public readonly int $documentsDeleted,
        public readonly int $emitAttemptsDeleted,
        public readonly int $webhookEventsDeleted,
        public readonly int $emailLogsDeleted,
        public readonly int $auditLogsDeleted,
        public readonly int $alertsDeleted,
        public readonly int $metricsDeleted,
        public readonly array $filesRemoved = [],
        public readonly array $fileErrors = [],
        public readonly int $redisJobsRemoved = 0,
        public readonly int $redisClaimsRemoved = 0,
        public readonly int $redisResiduesFound = 0,
        public readonly int $storageResiduesFound = 0,
        public readonly array $redisErrors = [],
        public readonly array $storageResidues = [],
        public readonly array $redisQueuesProcessed = [],
    ) {
    }

    public function isFullyPurged(): bool
    {
        return $this->cleanupStatus === self::STATUS_COMPLETE;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ruc' => $this->ruc,
            'tenant_slug' => $this->tenantSlug,
            'tenant_id' => $this->tenantId,
            'already_deleted' => $this->alreadyDeleted,
            'cleanup_status' => $this->cleanupStatus,
            'database_purged' => $this->databasePurged,
            'redis_purged' => $this->redisPurged,
            'storage_purged' => $this->storagePurged,
            'documents_deleted' => $this->documentsDeleted,
            'emit_attempts_deleted' => $this->emitAttemptsDeleted,
            'webhook_events_deleted' => $this->webhookEventsDeleted,
            'email_logs_deleted' => $this->emailLogsDeleted,
            'audit_logs_deleted' => $this->auditLogsDeleted,
            'alerts_deleted' => $this->alertsDeleted,
            'metrics_deleted' => $this->metricsDeleted,
            'files_removed' => $this->filesRemoved,
            'file_errors' => $this->fileErrors,
            'redis_jobs_removed' => $this->redisJobsRemoved,
            'redis_claims_removed' => $this->redisClaimsRemoved,
            'redis_residues_found' => $this->redisResiduesFound,
            'storage_residues_found' => $this->storageResiduesFound,
            'redis_errors' => $this->redisErrors,
            'storage_residues' => $this->storageResidues,
            'redis_queues_processed' => $this->redisQueuesProcessed,
        ];
    }
}
