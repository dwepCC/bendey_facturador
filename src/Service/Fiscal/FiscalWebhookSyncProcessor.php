<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use App\Repository\FiscalDocumentRepository;
use Psr\Log\LoggerInterface;

/**
 * Reintenta entrega webhook al ERP cuando falla.
 */
class FiscalWebhookSyncProcessor
{
    private FiscalDocumentRepository $repo;
    private FiscalWebhookService $webhook;
    private FiscalQueueService $queue;
    private LoggerInterface $logger;

    public function __construct(
        FiscalDocumentRepository $repo,
        FiscalWebhookService $webhook,
        FiscalQueueService $queue,
        LoggerInterface $logger
    ) {
        $this->repo = $repo;
        $this->webhook = $webhook;
        $this->queue = $queue;
        $this->logger = $logger;
    }

  /**
   * @param array<string, mixed> $job
   */
    public function process(array $job): void
    {
        $uuid = (string) ($job['document_uuid'] ?? '');
        $attempt = (int) ($job['attempt'] ?? 1);
        if ($uuid === '') {
            return;
        }

        $doc = $this->repo->findOneBy(['documentUuid' => $uuid]);
        if ($doc === null) {
            return;
        }

        try {
            $this->webhook->notifyStatus($doc);
        } catch (\Throwable $e) {
            $this->logger->warning('fiscal_webhook_sync_retry', [
                'uuid' => $uuid,
                'attempt' => $attempt,
                'error' => $e->getMessage(),
            ]);
            if ($attempt < 8) {
                $delay = min(3600, 15 * (2 ** ($attempt - 1)));
                $this->queue->scheduleRetry($uuid, $delay, FiscalQueueService::QUEUE_WEBHOOK_SYNC);
                $this->queue->push(FiscalQueueService::QUEUE_WEBHOOK_SYNC, [
                    'document_uuid' => $uuid,
                    'attempt' => $attempt + 1,
                ]);
            }
        }
    }
}
