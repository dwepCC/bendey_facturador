<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use App\Entity\FiscalDocument;
use App\Repository\FiscalDocumentRepository;

/**
 * Acciones masivas sobre colas fiscales (sin duplicar lógica de emisión).
 */
class FiscalBulkActionService
{
    private FiscalDocumentRepository $repo;
    private FiscalQueueService $queue;

    public function __construct(FiscalDocumentRepository $repo, FiscalQueueService $queue)
    {
        $this->repo = $repo;
        $this->queue = $queue;
    }

    /**
     * @param string[] $uuids
     * @return array{queued: int, skipped: int, errors: array<int, string>}
     */
    public function byUuids(array $uuids, string $action, int $max = 100, ?string $tenantSlug = null): array
    {
        $uuids = array_values(array_unique(array_filter(array_map('strval', $uuids))));
        if (count($uuids) > $max) {
            $uuids = array_slice($uuids, 0, $max);
        }

        $queued = 0;
        $skipped = 0;
        $errors = [];
        $queueName = $this->queueForAction($action);

        foreach ($uuids as $uuid) {
            $doc = $this->repo->findOneBy(['documentUuid' => $uuid]);
            if (!$doc instanceof FiscalDocument) {
                $errors[] = $uuid . ': no encontrado';
                continue;
            }
            if ($tenantSlug !== null && $doc->getTenantSlug() !== $tenantSlug) {
                $errors[] = $uuid . ': fuera de tenant';
                continue;
            }
            if ($this->shouldSkip($doc, $action)) {
                $skipped++;
                continue;
            }
            try {
                $this->queue->push($queueName, $this->payloadFor($doc, $action));
                $queued++;
            } catch (\Throwable $e) {
                $errors[] = $uuid . ': ' . $e->getMessage();
            }
        }

        return ['queued' => $queued, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{queued: int, skipped: int, errors: array<int, string>}
     */
    public function byFilters(array $filters, string $action, int $max = 100): array
    {
        $max = min(500, max(1, $max));
        $tenantSlug = !empty($filters['tenant_slug']) ? (string) $filters['tenant_slug'] : null;
        $filters['limit'] = $max;
        $filters['offset'] = 0;
        $docs = $this->repo->findByFilters($filters);
        $uuids = array_map(static fn (FiscalDocument $d) => $d->getDocumentUuid(), $docs);

        return $this->byUuids($uuids, $action, $max, $tenantSlug);
    }

    private function queueForAction(string $action): string
    {
        switch ($action) {
            case 'email':
                return FiscalQueueService::QUEUE_EMAIL;
            case 'poll':
                return FiscalQueueService::QUEUE_STATUS_POLL;
            case 'send':
            case 'retry':
            case 'force':
                return FiscalQueueService::QUEUE_EMIT;
            default:
                throw new \InvalidArgumentException('acción bulk no soportada');
        }
    }

    private function shouldSkip(FiscalDocument $doc, string $action): bool
    {
        if ($action === 'force') {
            return false;
        }
        if ($doc->getStatus() === FiscalDocument::STATUS_ACCEPTED && in_array($action, ['send', 'retry'], true)) {
            return true;
        }
        if ($action === 'email' && $doc->getCustomerEmail() === null) {
            return true;
        }

        return false;
    }

    private function payloadFor(FiscalDocument $doc, string $action): array
    {
        return ['document_uuid' => $doc->getDocumentUuid()];
    }
}
