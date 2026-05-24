<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Observability;

use App\Entity\FiscalAlert;
use App\Entity\FiscalAuditLog;
use App\Repository\EmpresaRepository;
use App\Repository\FiscalAlertRepository;
use App\Repository\FiscalAuditLogRepository;
use App\Service\Fiscal\FiscalQueueService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Detección y persistencia de alertas proactivas (sin envío externo aún).
 */
class FiscalAlertService
{
    private const QUEUE_SATURATION_THRESHOLD = 500;
    private const RETRY_ANOMALY_THRESHOLD = 50;
    private const CONSECUTIVE_ERRORS_THRESHOLD = 5;

    private EntityManagerInterface $em;
    private FiscalAlertRepository $alerts;
    private FiscalAuditLogRepository $auditLogs;
    private EmpresaRepository $empresas;
    private FiscalQueueService $queue;

    public function __construct(
        EntityManagerInterface $em,
        FiscalAlertRepository $alerts,
        FiscalAuditLogRepository $auditLogs,
        EmpresaRepository $empresas,
        FiscalQueueService $queue
    ) {
        $this->em = $em;
        $this->alerts = $alerts;
        $this->auditLogs = $auditLogs;
        $this->empresas = $empresas;
        $this->queue = $queue;
    }

    public function runDetection(): int
    {
        $created = 0;
        $created += $this->detectConnectionIssues();
        $created += $this->detectQueueSaturation();
        $created += $this->detectRetryAnomalies();
        $created += $this->detectConsecutiveErrors();
        return $created;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listOpen(int $limit = 50): array
    {
        return array_map(fn (FiscalAlert $a) => $this->serialize($a), $this->alerts->findOpen($limit));
    }

    public function countOpen(): int
    {
        return $this->alerts->countOpen();
    }

    private function detectConnectionIssues(): int
    {
        $created = 0;
        foreach ($this->empresas->findBy(['enabled' => true]) as $empresa) {
            $status = $empresa->getConnectionStatus();
            $slug = $empresa->getTenantSlug();
            if ($slug === null || $slug === '') {
                continue;
            }

            if ($status === 'certificate_expired') {
                $created += $this->openAlert(
                    FiscalAlert::TYPE_CERT_EXPIRING,
                    'critical',
                    $empresa->getTenantId(),
                    $slug,
                    $empresa->getRuc(),
                    'Certificado fiscal vencido para RUC ' . $empresa->getRuc()
                ) ? 1 : 0;
            } elseif (in_array($status, ['invalid_credentials', 'configuration_missing', 'error'], true)) {
                $created += $this->openAlert(
                    FiscalAlert::TYPE_TENANT_DISCONNECTED,
                    'warning',
                    $empresa->getTenantId(),
                    $slug,
                    $empresa->getRuc(),
                    'Tenant desconectado: ' . ($empresa->getConnectionError() ?? $status)
                ) ? 1 : 0;
            }
        }
        return $created;
    }

    private function detectQueueSaturation(): int
    {
        if (!$this->queue->isEnabled()) {
            return 0;
        }
        $pending = $this->queue->queueLength(FiscalQueueService::QUEUE_EMIT);
        if ($pending < self::QUEUE_SATURATION_THRESHOLD) {
            return 0;
        }
        return $this->openAlert(
            FiscalAlert::TYPE_QUEUE_SATURATED,
            'critical',
            null,
            null,
            null,
            sprintf('Cola fiscal saturada: %d jobs pendientes', $pending),
            ['pending_jobs' => $pending]
        ) ? 1 : 0;
    }

    private function detectRetryAnomalies(): int
    {
        $since = new \DateTimeImmutable('-24 hours');
        $conn = $this->em->getConnection();
        $rows = $conn->fetchAllAssociative(
            'SELECT tenant_slug, COUNT(*) AS retries FROM fiscal_audit_logs WHERE status = :st AND created_at >= :since AND tenant_slug IS NOT NULL GROUP BY tenant_slug HAVING retries >= :min',
            [
                'st' => FiscalAuditLog::STATUS_RETRYING,
                'since' => $since->format('Y-m-d H:i:s'),
                'min' => self::RETRY_ANOMALY_THRESHOLD,
            ]
        );
        $created = 0;
        foreach ($rows as $row) {
            $created += $this->openAlert(
                FiscalAlert::TYPE_RETRY_ANOMALY,
                'warning',
                null,
                (string) $row['tenant_slug'],
                null,
                sprintf('Retries anormales en %s: %d en 24h', $row['tenant_slug'], $row['retries']),
                ['retries_24h' => (int) $row['retries']]
            ) ? 1 : 0;
        }
        return $created;
    }

    private function detectConsecutiveErrors(): int
    {
        $conn = $this->em->getConnection();
        $rows = $conn->fetchAllAssociative(
            <<<'SQL'
SELECT tenant_slug, COUNT(*) AS failures FROM (
  SELECT tenant_slug, status FROM fiscal_audit_logs
  WHERE tenant_slug IS NOT NULL AND event_type IN ('fiscal_emit_failed','fiscal_emit_success')
  ORDER BY created_at DESC LIMIT 500
) recent GROUP BY tenant_slug HAVING failures >= :min
SQL,
            ['min' => self::CONSECUTIVE_ERRORS_THRESHOLD]
        );
        // Verificación más precisa: últimos N eventos del tenant
        $created = 0;
        foreach ($rows as $row) {
            $slug = (string) $row['tenant_slug'];
            $recent = $conn->fetchAllAssociative(
                'SELECT status FROM fiscal_audit_logs WHERE tenant_slug = :slug AND event_type IN (:e1,:e2) ORDER BY created_at DESC LIMIT :lim',
                ['slug' => $slug, 'e1' => 'fiscal_emit_failed', 'e2' => 'fiscal_emit_success', 'lim' => self::CONSECUTIVE_ERRORS_THRESHOLD],
                ['lim' => \Doctrine\DBAL\ParameterType::INTEGER]
            );
            if (count($recent) < self::CONSECUTIVE_ERRORS_THRESHOLD) {
                continue;
            }
            $allFailed = true;
            foreach ($recent as $r) {
                if (($r['status'] ?? '') !== FiscalAuditLog::STATUS_FAILED) {
                    $allFailed = false;
                    break;
                }
            }
            if (!$allFailed) {
                continue;
            }
            $created += $this->openAlert(
                FiscalAlert::TYPE_CONSECUTIVE_ERRORS,
                'critical',
                null,
                $slug,
                null,
                sprintf('%d errores consecutivos en tenant %s', self::CONSECUTIVE_ERRORS_THRESHOLD, $slug)
            ) ? 1 : 0;
        }
        return $created;
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function openAlert(
        string $type,
        string $severity,
        ?int $tenantId,
        ?string $tenantSlug,
        ?string $ruc,
        string $message,
        array $meta = []
    ): bool {
        $existing = $this->alerts->createQueryBuilder('a')
            ->where('a.alertType = :t')
            ->andWhere('a.resolvedAt IS NULL')
            ->setParameter('t', $type);

        if ($tenantSlug !== null && $tenantSlug !== '') {
            $existing->andWhere('a.tenantSlug = :s')->setParameter('s', $tenantSlug);
        } else {
            $existing->andWhere('a.tenantSlug IS NULL');
        }

        if ($existing->setMaxResults(1)->getQuery()->getOneOrNullResult() !== null) {
            return false;
        }

        $alert = new FiscalAlert();
        $alert->setAlertType($type);
        $alert->setSeverity($severity);
        $alert->setMessage($message);
        if ($tenantId !== null) {
            $alert->setTenantId($tenantId);
        }
        if ($tenantSlug !== null) {
            $alert->setTenantSlug($tenantSlug);
        }
        if ($ruc !== null) {
            $alert->setRuc($ruc);
        }
        if ($meta !== []) {
            $alert->setMetadataJson(json_encode($meta, JSON_UNESCAPED_UNICODE) ?: null);
        }

        try {
            $this->em->persist($alert);
            $this->em->flush();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(FiscalAlert $a): array
    {
        return [
            'id' => $a->getId(),
            'tenant_id' => $a->getTenantId(),
            'tenant_slug' => $a->getTenantSlug(),
            'ruc' => $a->getRuc(),
            'alert_type' => $a->getAlertType(),
            'severity' => $a->getSeverity(),
            'message' => $a->getMessage(),
            'metadata' => $a->getMetadataJson() ? json_decode($a->getMetadataJson(), true) : null,
            'created_at' => $a->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}
