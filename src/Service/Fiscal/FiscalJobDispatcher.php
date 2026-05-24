<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use Psr\Log\LoggerInterface;

/**
 * Encola trabajos fiscales en Redis o los ejecuta en línea si REDIS_URL no está configurado.
 */
class FiscalJobDispatcher
{
    private FiscalQueueService $queue;
    private FiscalEmitProcessor $emitProcessor;
    private FiscalEmailProcessor $emailProcessor;
    private FiscalWebhookSyncProcessor $webhookSyncProcessor;
    private FiscalStatusPollProcessor $statusPollProcessor;
    private LoggerInterface $logger;

    public function __construct(
        FiscalQueueService $queue,
        FiscalEmitProcessor $emitProcessor,
        FiscalEmailProcessor $emailProcessor,
        FiscalWebhookSyncProcessor $webhookSyncProcessor,
        FiscalStatusPollProcessor $statusPollProcessor,
        LoggerInterface $logger
    ) {
        $this->queue = $queue;
        $this->emitProcessor = $emitProcessor;
        $this->emailProcessor = $emailProcessor;
        $this->webhookSyncProcessor = $webhookSyncProcessor;
        $this->statusPollProcessor = $statusPollProcessor;
        $this->logger = $logger;
    }

    public function isAsync(): bool
    {
        return $this->queue->isEnabled();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function dispatch(string $queue, array $payload): void
    {
        if ($this->queue->isEnabled()) {
            $this->queue->pushToRedis($queue, $payload);
            return;
        }
        $this->dispatchSync($queue, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function dispatchSync(string $queue, array $payload): void
    {
        $uuid = (string) ($payload['document_uuid'] ?? '');
        if ($uuid === '') {
            return;
        }

        $this->logger->info('fiscal_sync_dispatch', [
            'queue' => $queue,
            'document_uuid' => $uuid,
        ]);

        switch ($queue) {
            case FiscalQueueService::QUEUE_EMIT:
            case FiscalQueueService::QUEUE_RETRY:
            case FiscalQueueService::QUEUE_PSE_RETRY:
                $this->emitProcessor->processByUuid($uuid);
                return;
            case FiscalQueueService::QUEUE_EMAIL:
                $this->emailProcessor->processByUuid($uuid);
                return;
            case FiscalQueueService::QUEUE_WEBHOOK_SYNC:
                $this->webhookSyncProcessor->process($payload);
                return;
            case FiscalQueueService::QUEUE_STATUS_POLL:
                $this->statusPollProcessor->processByUuid($uuid, (int) ($payload['attempt'] ?? 1));
                return;
            default:
                $this->logger->warning('fiscal_sync_dispatch_unknown_queue', ['queue' => $queue]);
        }
    }
}
