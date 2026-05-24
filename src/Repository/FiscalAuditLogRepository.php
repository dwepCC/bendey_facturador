<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FiscalAuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FiscalAuditLog>
 */
class FiscalAuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FiscalAuditLog::class);
    }

    /**
     * @return FiscalAuditLog[]
     */
    public function findByDocumentUuid(string $uuid): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.documentUuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->orderBy('a.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

  /**
     * @return array<int, array<string, mixed>>
     */
    public function globalSummarySince(\DateTimeInterface $since): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = <<<'SQL'
SELECT
  COUNT(*) AS total_events,
  SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS successes,
  SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failures,
  SUM(CASE WHEN status = 'retrying' THEN 1 ELSE 0 END) AS retries,
  AVG(duration_ms) AS avg_duration_ms
FROM fiscal_audit_logs
WHERE created_at >= :since
SQL;
        $row = $conn->fetchAssociative($sql, ['since' => $since->format('Y-m-d H:i:s')]);
        return is_array($row) ? $row : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function errorsByProviderSince(\DateTimeInterface $since): array
    {
        $conn = $this->getEntityManager()->getConnection();
        return $conn->fetchAllAssociative(
            'SELECT provider, COUNT(*) AS errors FROM fiscal_audit_logs WHERE status = :st AND created_at >= :since AND provider IS NOT NULL GROUP BY provider ORDER BY errors DESC',
            ['st' => FiscalAuditLog::STATUS_FAILED, 'since' => $since->format('Y-m-d H:i:s')]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function emissionsByHourSince(\DateTimeInterface $since): array
    {
        $conn = $this->getEntityManager()->getConnection();
        return $conn->fetchAllAssociative(
            "SELECT DATE_FORMAT(created_at, '%Y-%m-%d %H:00') AS hour_bucket, COUNT(*) AS total FROM fiscal_audit_logs WHERE event_type IN ('fiscal_emit_success','fiscal_document_queued') AND created_at >= :since GROUP BY hour_bucket ORDER BY hour_bucket",
            ['since' => $since->format('Y-m-d H:i:s')]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function tenantOperationsSummary(int $hours = 24): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $since = (new \DateTimeImmutable("-{$hours} hours"))->format('Y-m-d H:i:s');
        return $conn->fetchAllAssociative(
            <<<'SQL'
SELECT
  tenant_slug,
  MAX(ruc) AS ruc,
  MAX(send_mode) AS send_mode,
  MAX(provider) AS provider,
  SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS errors_24h,
  SUM(CASE WHEN status = 'retrying' THEN 1 ELSE 0 END) AS retries_24h,
  AVG(duration_ms) AS avg_duration_ms,
  MAX(created_at) AS last_event_at,
  MAX(CASE WHEN status = 'failed' THEN error_message END) AS last_error
FROM fiscal_audit_logs
WHERE tenant_slug IS NOT NULL AND created_at >= :since
GROUP BY tenant_slug
ORDER BY errors_24h DESC, tenant_slug ASC
SQL,
            ['since' => $since]
        );
    }
}
