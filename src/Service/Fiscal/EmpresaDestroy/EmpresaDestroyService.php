<?php

declare(strict_types=1);

namespace App\Service\Fiscal\EmpresaDestroy;

use App\Entity\Empresa;
use App\Repository\EmpresaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Elimina completamente una empresa fiscal y todos sus datos en el facturador.
 * Independiente del ERP; no consulta backend_go ni panel central.
 */
class EmpresaDestroyService
{
    public function __construct(
        private readonly EmpresaRepository $empresaRepository,
        private readonly EntityManagerInterface $em,
        private readonly EmpresaDestroyRedisPurgeService $redisPurge,
        private readonly EmpresaDestroyStoragePurgeService $storagePurge,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array{tenant_slug?: string|null, tenant_id?: int|null} $hints
     */
    public function destroy(string $ruc, string $adminUsername, array $hints = []): EmpresaDestroyResult
    {
        $ruc = preg_replace('/\D/', '', trim($ruc)) ?? '';
        if ($ruc === '' || strlen($ruc) !== 11) {
            throw new \InvalidArgumentException('RUC inválido (11 dígitos).');
        }

        $tenantSlugHint = $this->nullableString($hints['tenant_slug'] ?? null);
        $tenantIdHint = isset($hints['tenant_id']) && (int) $hints['tenant_id'] > 0
            ? (int) $hints['tenant_id']
            : null;

        $empresa = $this->empresaRepository->findByRuc($ruc);
        $alreadyDeleted = $empresa === null;

        $counts = [
            'documents' => 0,
            'emit_attempts' => 0,
            'webhook_events' => 0,
            'email_logs' => 0,
            'audit_logs' => 0,
            'alerts' => 0,
            'metrics' => 0,
        ];

        $purgeContext = null;
        $databasePurged = $alreadyDeleted;

        if ($empresa instanceof Empresa) {
            $tenantSlug = $empresa->getTenantSlug();
            $tenantId = $empresa->getTenantId();

            $this->logger->warning('[EmpresaDestroy] Eliminación fiscal solicitada', [
                'admin_user' => $adminUsername,
                'ruc' => $ruc,
                'tenant_slug' => $tenantSlug,
                'tenant_id' => $tenantId,
                'action' => 'fiscal_company_destroy_start',
                'at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ]);

            $documentRows = $this->fetchDocumentRows($ruc, $tenantSlug, $tenantId);
            $documentUuids = array_column($documentRows, 'document_uuid');
            $fingerprints = array_values(array_filter(array_column($documentRows, 'fiscal_fingerprint')));

            $purgeContext = EmpresaDestroyPurgeContext::fromEmpresa($empresa, $documentUuids, $fingerprints);

            $conn = $this->em->getConnection();
            try {
                $this->em->wrapInTransaction(function () use (
                    $conn,
                    $ruc,
                    $tenantSlug,
                    $tenantId,
                    $documentUuids,
                    $empresa,
                    &$counts
                ) {
                    if ($documentUuids !== []) {
                        $in = implode(',', array_fill(0, count($documentUuids), '?'));
                        $counts['email_logs'] = (int) $conn->executeStatement(
                            "DELETE FROM outbound_email_logs WHERE document_uuid IN ($in)",
                            $documentUuids
                        );
                        $counts['webhook_events'] = (int) $conn->executeStatement(
                            "DELETE FROM fiscal_webhook_events WHERE document_uuid IN ($in)",
                            $documentUuids
                        );
                        $counts['emit_attempts'] = (int) $conn->executeStatement(
                            "DELETE FROM fiscal_emit_attempts WHERE document_uuid IN ($in)",
                            $documentUuids
                        );
                    }

                    $counts['documents'] = (int) $conn->executeStatement(
                        $this->documentsDeleteSql($tenantSlug, $tenantId),
                        $this->documentsDeleteParams($ruc, $tenantSlug, $tenantId)
                    );

                    $counts['audit_logs'] = (int) $conn->executeStatement(
                        $this->companyScopeSql('fiscal_audit_logs', $tenantSlug, $tenantId),
                        $this->companyScopeParams($ruc, $tenantSlug, $tenantId)
                    );
                    $counts['alerts'] = (int) $conn->executeStatement(
                        $this->companyScopeSql('fiscal_alerts', $tenantSlug, $tenantId),
                        $this->companyScopeParams($ruc, $tenantSlug, $tenantId)
                    );
                    $counts['metrics'] = (int) $conn->executeStatement(
                        $this->companyScopeSql('fiscal_tenant_metrics', $tenantSlug, $tenantId),
                        $this->companyScopeParams($ruc, $tenantSlug, $tenantId)
                    );

                    $this->em->remove($empresa);
                    $this->em->flush();
                });
                $databasePurged = true;
            } catch (\Throwable $e) {
                $this->logger->error('[EmpresaDestroy] Error en purge de base de datos', [
                    'ruc' => $ruc,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        } else {
            $this->logger->warning('[EmpresaDestroy] Reintento de limpieza — empresa ya ausente en BD', [
                'admin_user' => $adminUsername,
                'ruc' => $ruc,
                'tenant_slug_hint' => $tenantSlugHint,
                'tenant_id_hint' => $tenantIdHint,
                'action' => 'fiscal_company_destroy_orphan_cleanup',
                'at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ]);

            $purgeContext = EmpresaDestroyPurgeContext::forOrphanCleanup($ruc, $tenantSlugHint, $tenantIdHint);
            $databasePurged = !$this->databaseHasResidues($ruc, $tenantSlugHint, $tenantIdHint);
        }

        $external = $this->purgeExternalResources($purgeContext);

        $cleanupStatus = $this->resolveCleanupStatus(
            $databasePurged,
            $external['redis_residues_found'],
            $external['storage_residues_found'],
            $external['redis_errors'],
            $external['file_errors']
        );

        $this->logger->warning('[EmpresaDestroy] Eliminación fiscal finalizada', [
            'admin_user' => $adminUsername,
            'ruc' => $ruc,
            'tenant_slug' => $purgeContext->tenantSlug,
            'tenant_id' => $purgeContext->tenantId,
            'already_deleted' => $alreadyDeleted,
            'cleanup_status' => $cleanupStatus,
            'database_purged' => $databasePurged,
            'documents_deleted' => $counts['documents'],
            'redis_jobs_removed' => $external['redis_jobs_removed'],
            'redis_residues_found' => $external['redis_residues_found'],
            'storage_residues_found' => $external['storage_residues_found'],
            'files_removed' => count($external['files_removed']),
            'at' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ]);

        return new EmpresaDestroyResult(
            ruc: $ruc,
            tenantSlug: $purgeContext->tenantSlug ?? $tenantSlugHint,
            tenantId: $purgeContext->tenantId ?? $tenantIdHint,
            alreadyDeleted: $alreadyDeleted,
            cleanupStatus: $cleanupStatus,
            databasePurged: $databasePurged,
            redisPurged: $external['redis_residues_found'] === 0 && $external['redis_errors'] === [],
            storagePurged: $external['storage_residues_found'] === 0 && $external['file_errors'] === [],
            documentsDeleted: $counts['documents'],
            emitAttemptsDeleted: $counts['emit_attempts'],
            webhookEventsDeleted: $counts['webhook_events'],
            emailLogsDeleted: $counts['email_logs'],
            auditLogsDeleted: $counts['audit_logs'],
            alertsDeleted: $counts['alerts'],
            metricsDeleted: $counts['metrics'],
            filesRemoved: $external['files_removed'],
            fileErrors: $external['file_errors'],
            redisJobsRemoved: $external['redis_jobs_removed'],
            redisClaimsRemoved: $external['redis_claims_removed'],
            redisResiduesFound: $external['redis_residues_found'],
            storageResiduesFound: $external['storage_residues_found'],
            redisErrors: $external['redis_errors'],
            storageResidues: $external['storage_residues'],
            redisQueuesProcessed: $external['redis_queues_processed'],
        );
    }

    /**
     * @return array{
     *     redis_jobs_removed: int,
     *     redis_claims_removed: int,
     *     redis_errors: list<string>,
     *     redis_queues_processed: list<array{key: string, type: string, removed: int}>,
     *     redis_residues_found: int,
     *     files_removed: list<string>,
     *     file_errors: list<string>,
     *     storage_residues: list<string>,
     *     storage_residues_found: int
     * }
     */
    private function purgeExternalResources(EmpresaDestroyPurgeContext $ctx): array
    {
        $redisStats = $this->redisPurge->purge($ctx);
        $storageStats = $this->storagePurge->purgeByContext($ctx);

        if ($redisStats['residues_found'] > 0 || $storageStats['residues_found'] !== []) {
            $this->logger->info('[EmpresaDestroy] Reintento de purge por residuos detectados', [
                'ruc' => $ctx->ruc,
                'redis_residues' => $redisStats['residues_found'],
                'storage_residues' => count($storageStats['residues_found']),
            ]);

            $retryRedis = $this->redisPurge->purge($ctx);
            $retryStorage = $this->storagePurge->purgeByContext($ctx);

            $redisStats['jobs_removed'] += $retryRedis['jobs_removed'];
            $redisStats['claims_removed'] += $retryRedis['claims_removed'];
            $redisStats['errors'] = array_values(array_unique([...$redisStats['errors'], ...$retryRedis['errors']]));
            $redisStats['queues_processed'] = [...$redisStats['queues_processed'], ...$retryRedis['queues_processed']];
            $redisStats['residues_found'] = $this->redisPurge->countResidues($ctx);

            $storageStats['removed'] = array_values(array_unique([...$storageStats['removed'], ...$retryStorage['removed']]));
            $storageStats['errors'] = array_values(array_unique([...$storageStats['errors'], ...$retryStorage['errors']]));
            $storageStats['residues_found'] = $this->storagePurge->findResidues($ctx);
        }

        return [
            'redis_jobs_removed' => $redisStats['jobs_removed'],
            'redis_claims_removed' => $redisStats['claims_removed'],
            'redis_errors' => $redisStats['errors'],
            'redis_queues_processed' => $redisStats['queues_processed'],
            'redis_residues_found' => $redisStats['residues_found'],
            'files_removed' => $storageStats['removed'],
            'file_errors' => $storageStats['errors'],
            'storage_residues' => $storageStats['residues_found'],
            'storage_residues_found' => count($storageStats['residues_found']),
        ];
    }

    /**
     * @param list<string> $redisErrors
     * @param list<string> $fileErrors
     */
    private function resolveCleanupStatus(
        bool $databasePurged,
        int $redisResidues,
        int $storageResidues,
        array $redisErrors,
        array $fileErrors
    ): string {
        if (!$databasePurged) {
            return EmpresaDestroyResult::STATUS_FAILED;
        }

        $externalClean = $redisResidues === 0
            && $storageResidues === 0
            && $redisErrors === []
            && $fileErrors === [];

        return $externalClean
            ? EmpresaDestroyResult::STATUS_COMPLETE
            : EmpresaDestroyResult::STATUS_PARTIAL;
    }

    private function databaseHasResidues(string $ruc, ?string $tenantSlug, ?int $tenantId): bool
    {
        $conn = $this->em->getConnection();

        if ((int) $conn->fetchOne('SELECT COUNT(*) FROM empresa WHERE ruc = ?', [$ruc]) > 0) {
            return true;
        }

        $docSql = 'SELECT COUNT(*) FROM fiscal_documents WHERE ' . $this->documentsWhereClause($tenantSlug, $tenantId);
        if ((int) $conn->fetchOne($docSql, $this->documentsDeleteParams($ruc, $tenantSlug, $tenantId)) > 0) {
            return true;
        }

        foreach (['fiscal_audit_logs', 'fiscal_alerts', 'fiscal_tenant_metrics'] as $table) {
            $sql = 'SELECT COUNT(*) FROM ' . $table . ' WHERE ' . implode(' OR ', ['ruc = :ruc']);
            $params = ['ruc' => $ruc];
            if ($tenantSlug !== null && $tenantSlug !== '') {
                $sql .= ' OR tenant_slug = :slug';
                $params['slug'] = $tenantSlug;
            }
            if ($tenantId !== null && $tenantId > 0) {
                $sql .= ' OR tenant_id = :tid';
                $params['tid'] = $tenantId;
            }
            if ((int) $conn->fetchOne($sql, $params) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{document_uuid: string, fiscal_fingerprint: string|null}>
     */
    private function fetchDocumentRows(string $ruc, ?string $tenantSlug, ?int $tenantId): array
    {
        $conn = $this->em->getConnection();
        $sql = 'SELECT document_uuid, fiscal_fingerprint FROM fiscal_documents WHERE '
            . $this->documentsWhereClause($tenantSlug, $tenantId);
        /** @var list<array{document_uuid: string, fiscal_fingerprint: string|null}> $rows */
        $rows = $conn->fetchAllAssociative($sql, $this->documentsDeleteParams($ruc, $tenantSlug, $tenantId));

        return $rows;
    }

    private function documentsDeleteSql(?string $tenantSlug, ?int $tenantId): string
    {
        return 'DELETE FROM fiscal_documents WHERE ' . $this->documentsWhereClause($tenantSlug, $tenantId);
    }

    private function documentsWhereClause(?string $tenantSlug, ?int $tenantId): string
    {
        $parts = [];
        if ($tenantSlug !== null && $tenantSlug !== '') {
            $parts[] = 'tenant_slug = :slug';
        }
        if ($tenantId !== null && $tenantId > 0) {
            $parts[] = 'tenant_id = :tid';
        }
        if ($parts === []) {
            return 'snapshot_json LIKE :rucLike';
        }

        return implode(' OR ', $parts);
    }

    /**
     * @return array<string, mixed>
     */
    private function documentsDeleteParams(string $ruc, ?string $tenantSlug, ?int $tenantId): array
    {
        $params = ['ruc' => $ruc, 'rucLike' => '%' . $ruc . '%'];
        if ($tenantSlug !== null && $tenantSlug !== '') {
            $params['slug'] = $tenantSlug;
        }
        if ($tenantId !== null && $tenantId > 0) {
            $params['tid'] = $tenantId;
        }

        return $params;
    }

    private function companyScopeSql(string $table, ?string $tenantSlug, ?int $tenantId): string
    {
        $parts = ['ruc = :ruc'];
        if ($tenantSlug !== null && $tenantSlug !== '') {
            $parts[] = 'tenant_slug = :slug';
        }
        if ($tenantId !== null && $tenantId > 0) {
            $parts[] = 'tenant_id = :tid';
        }

        return "DELETE FROM {$table} WHERE " . implode(' OR ', $parts);
    }

    /**
     * @return array<string, mixed>
     */
    private function companyScopeParams(string $ruc, ?string $tenantSlug, ?int $tenantId): array
    {
        $params = ['ruc' => $ruc];
        if ($tenantSlug !== null && $tenantSlug !== '') {
            $params['slug'] = $tenantSlug;
        }
        if ($tenantId !== null && $tenantId > 0) {
            $params['tid'] = $tenantId;
        }

        return $params;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
