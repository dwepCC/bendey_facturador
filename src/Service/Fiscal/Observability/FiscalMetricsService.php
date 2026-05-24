<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Observability;

use App\Entity\FiscalTenantMetric;
use App\Repository\FiscalTenantMetricRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * KPIs agregados por tenant (día/semana/mes) — async desde audit persist.
 */
class FiscalMetricsService
{
    private EntityManagerInterface $em;
    private FiscalTenantMetricRepository $repo;

    public function __construct(EntityManagerInterface $em, FiscalTenantMetricRepository $repo)
    {
        $this->em = $em;
        $this->repo = $repo;
    }

    /**
     * @param array<string, mixed> $entry
     */
    public function recordFromAuditEntry(array $entry): void
    {
        $eventType = (string) ($entry['event_type'] ?? '');
        $tenantId = isset($entry['tenant_id']) ? (int) $entry['tenant_id'] : 0;
        $tenantSlug = (string) ($entry['tenant_slug'] ?? '');
        if ($tenantId <= 0 || $tenantSlug === '') {
            return;
        }

        try {
            $today = new \DateTimeImmutable('today');
            $provider = !empty($entry['provider']) ? (string) $entry['provider'] : null;
            $metric = $this->repo->findForPeriod($tenantId, $today, 'day', $provider);
            if ($metric === null) {
                $metric = new FiscalTenantMetric();
                $metric->setTenantId($tenantId);
                $metric->setTenantSlug($tenantSlug);
                $metric->setPeriodDate($today);
                $metric->setPeriodType('day');
                $metric->setProvider($provider);
                $this->em->persist($metric);
            }

            if (!empty($entry['ruc'])) {
                $metric->setRuc((string) $entry['ruc']);
            }
            if (!empty($entry['send_mode'])) {
                $metric->setSendMode((string) $entry['send_mode']);
            }

            if ($eventType === 'fiscal_emit_success') {
                $metric->setDocumentsEmitted($metric->getDocumentsEmitted() + 1);
                $metric->setDocumentsAccepted($metric->getDocumentsAccepted() + 1);
                $this->updateAvgDuration($metric, isset($entry['duration_ms']) ? (int) $entry['duration_ms'] : null);
            } elseif ($eventType === 'fiscal_emit_failed') {
                $metric->setDocumentsEmitted($metric->getDocumentsEmitted() + 1);
                $metric->setErrors($metric->getErrors() + 1);
            } elseif ($eventType === 'fiscal_retry_scheduled') {
                $metric->setRetries($metric->getRetries() + 1);
            } elseif ($eventType === 'fiscal_document_queued') {
                // contabiliza intención de emisión
                $metric->setDocumentsEmitted($metric->getDocumentsEmitted() + 1);
            }

            $this->recalcSuccessRate($metric);
            $metric->touchUpdated();
            $this->em->flush();
        } catch (\Throwable) {
            // Nunca bloquear emisión
        }
    }

    private function updateAvgDuration(FiscalTenantMetric $metric, ?int $durationMs): void
    {
        if ($durationMs === null || $durationMs <= 0) {
            return;
        }
        $prev = $metric->getAvgDurationMs();
        $accepted = max(1, $metric->getDocumentsAccepted());
        $metric->setAvgDurationMs($prev === null
            ? $durationMs
            : (int) round(($prev * ($accepted - 1) + $durationMs) / $accepted));
    }

    private function recalcSuccessRate(FiscalTenantMetric $metric): void
    {
        $total = $metric->getDocumentsEmitted();
        if ($total <= 0) {
            $metric->setSuccessRate(null);
            return;
        }
        $rate = round(($metric->getDocumentsAccepted() / $total) * 100, 2);
        $metric->setSuccessRate((string) $rate);
    }
}
