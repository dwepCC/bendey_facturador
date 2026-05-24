<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Observability;

use App\Entity\FiscalDocument;
use App\Repository\EmpresaRepository;
use App\Repository\FiscalAlertRepository;
use App\Service\Fiscal\FiscalQueueService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Health check fiscal para operaciones SaaS.
 */
class FiscalHealthService
{
    private FiscalQueueService $queue;
    private EmpresaRepository $empresas;
    private EntityManagerInterface $em;
    private FiscalAlertRepository $alerts;

    public function __construct(
        FiscalQueueService $queue,
        EmpresaRepository $empresas,
        EntityManagerInterface $em,
        FiscalAlertRepository $alerts
    ) {
        $this->queue = $queue;
        $this->empresas = $empresas;
        $this->em = $em;
        $this->alerts = $alerts;
    }

    /**
     * @return array<string, mixed>
     */
    public function check(): array
    {
        $redisConfigured = $this->queue->isEnabled();
        $redisConnected = $this->queue->isReachable();
        $emitPending = $redisConnected ? $this->queue->queueLength(FiscalQueueService::QUEUE_EMIT) : 0;
        $retryPending = $redisConnected
            ? $this->queue->scheduledRetryCount(FiscalQueueService::QUEUE_RETRY)
                + $this->queue->scheduledRetryCount(FiscalQueueService::QUEUE_PSE_RETRY)
            : 0;
        $auditPending = $redisConnected ? $this->queue->queueLength(FiscalQueueService::QUEUE_AUDIT) : 0;

        $heartbeatAge = $this->queue->workerHeartbeatAge();
        $workerActive = $heartbeatAge !== null && $heartbeatAge <= 120;

        $dbOk = $this->pingDb();
        $providerStatus = $this->providerStatusSummary();
        $sunatConnectivity = $this->sunatConnectivitySummary();

        $failedDocs = (int) $this->em->createQueryBuilder()
            ->select('COUNT(d.id)')
            ->from(FiscalDocument::class, 'd')
            ->where('d.status IN (:st)')
            ->setParameter('st', [FiscalDocument::STATUS_ERROR, FiscalDocument::STATUS_REJECTED])
            ->getQuery()
            ->getSingleScalarResult();

        $openAlerts = $this->alerts->countOpen();
        $criticalAlerts = (int) $this->alerts->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.resolvedAt IS NULL')
            ->andWhere('a.severity = :s')
            ->setParameter('s', 'critical')
            ->getQuery()
            ->getSingleScalarResult();

        $status = $this->resolveOverallStatus(
            $redisConnected,
            $dbOk,
            $workerActive,
            $emitPending,
            $criticalAlerts
        );

        return [
            'status' => $status,
            'queue_status' => [
                'emit' => $emitPending,
                'retry' => $retryPending,
                'audit' => $auditPending,
            ],
            'redis_connected' => $redisConnected,
            'redis_configured' => $redisConfigured,
            'pending_jobs' => $emitPending + $retryPending,
            'failed_jobs' => $failedDocs,
            'worker_count' => $workerActive ? 1 : 0,
            'worker_heartbeat_age_sec' => $heartbeatAge,
            'provider_status' => $providerStatus,
            'sunat_connectivity' => $sunatConnectivity,
            'db_status' => $dbOk ? 'ok' : 'error',
            'open_alerts' => $openAlerts,
            'critical_alerts' => $criticalAlerts,
            'checked_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ];
    }

    private function pingDb(): bool
    {
        try {
            $this->em->getConnection()->executeQuery('SELECT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function providerStatusSummary(): array
    {
        $conn = $this->em->getConnection();
        $rows = $conn->fetchAllAssociative(
            "SELECT COALESCE(provider, send_mode, 'unknown') AS provider_key, connection_status, COUNT(*) AS total FROM empresa WHERE enabled = 1 GROUP BY COALESCE(provider, send_mode, 'unknown'), connection_status"
        );
        $byProvider = [];
        foreach ($rows as $row) {
            $p = (string) $row['provider_key'];
            if (!isset($byProvider[$p])) {
                $byProvider[$p] = ['connected' => 0, 'disconnected' => 0, 'total' => 0];
            }
            $byProvider[$p]['total'] += (int) $row['total'];
            if (($row['connection_status'] ?? '') === 'connected') {
                $byProvider[$p]['connected'] += (int) $row['total'];
            } else {
                $byProvider[$p]['disconnected'] += (int) $row['total'];
            }
        }
        return $byProvider;
    }

    /**
     * @return array<string, mixed>
     */
    private function sunatConnectivitySummary(): array
    {
        $conn = $this->em->getConnection();
        $row = $conn->fetchAssociative(
            'SELECT SUM(CASE WHEN connection_status = \'connected\' THEN 1 ELSE 0 END) AS connected, COUNT(*) AS total FROM empresa WHERE enabled = 1 AND send_mode = \'sunat_direct\''
        );
        $connected = (int) ($row['connected'] ?? 0);
        $total = (int) ($row['total'] ?? 0);
        return [
            'connected' => $connected,
            'total' => $total,
            'ratio' => $total > 0 ? round($connected / $total, 2) : null,
        ];
    }

    private function resolveOverallStatus(
        bool $redisConnected,
        bool $db,
        bool $worker,
        int $emitPending,
        int $criticalAlerts
    ): string {
        if (!$db) {
            return 'critical';
        }
        if (!$redisConnected) {
            return 'degraded';
        }
        if ($criticalAlerts > 0 || !$worker || $emitPending >= 500) {
            return 'critical';
        }
        if ($emitPending >= 100) {
            return 'degraded';
        }
        return 'healthy';
    }
}
