<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use App\Entity\FiscalDocument;
use App\Repository\FiscalDocumentRepository;
use Psr\Log\LoggerInterface;

/**
 * Recupera documentos en status queued sin job Redis (huérfanos de cola).
 */
class FiscalOrphanRecoveryService
{
    private FiscalDocumentRepository $repo;
    private FiscalDocumentService $documentService;
    private LoggerInterface $logger;

    public function __construct(
        FiscalDocumentRepository $repo,
        FiscalDocumentService $documentService,
        LoggerInterface $logger
    ) {
        $this->repo = $repo;
        $this->documentService = $documentService;
        $this->logger = $logger;
    }

    /**
     * @return array{recovered: int, skipped: int, errors: list<string>}
     */
    public function recover(int $minAgeMinutes = 5, int $limit = 50): array
    {
        $limit = min(500, max(1, $limit));
        $minAgeMinutes = max(0, $minAgeMinutes);
        $before = new \DateTimeImmutable('-' . $minAgeMinutes . ' minutes');

        $docs = $this->repo->findOrphanedQueued($before, $limit);
        $recovered = 0;
        $skipped = 0;
        $errors = [];

        foreach ($docs as $doc) {
            if (!$doc instanceof FiscalDocument) {
                continue;
            }
            $ruc = FiscalDocumentService::resolveRucFromSnapshot($doc->getSnapshotJson());
            if ($ruc === null || $ruc === '') {
                $skipped++;
                $errors[] = $doc->getDocumentUuid() . ': RUC no encontrado en snapshot';
                continue;
            }
            try {
                $this->documentService->requeueEmit($doc, $ruc);
                $recovered++;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = $doc->getDocumentUuid() . ': ' . $e->getMessage();
                $this->logger->error('fiscal_orphan_recover_failed', [
                    'document_uuid' => $doc->getDocumentUuid(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($recovered > 0) {
            $this->logger->info('fiscal_orphans_recovered', [
                'recovered' => $recovered,
                'min_age_minutes' => $minAgeMinutes,
            ]);
        }

        return ['recovered' => $recovered, 'skipped' => $skipped, 'errors' => $errors];
    }
}
