<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Fiscal\FiscalEmailProcessor;
use App\Service\Fiscal\FiscalEmitProcessor;
use App\Service\Fiscal\FiscalQueueService;
use App\Service\Fiscal\FiscalStatusPollProcessor;
use App\Service\Fiscal\FiscalWebhookSyncProcessor;
use App\Service\Fiscal\Observability\FiscalAuditService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Workers Redis fiscales (escalar N instancias).
 */
#[AsCommand(name: 'app:fiscal:worker')]
class FiscalWorkerCommand extends Command
{
    private FiscalQueueService $queue;
    private FiscalEmitProcessor $emitProcessor;
    private FiscalEmailProcessor $emailProcessor;
    private FiscalWebhookSyncProcessor $webhookSyncProcessor;
    private FiscalStatusPollProcessor $statusPollProcessor;
    private FiscalAuditService $audit;
    private LoggerInterface $logger;

    public function __construct(
        FiscalQueueService $queue,
        FiscalEmitProcessor $emitProcessor,
        FiscalEmailProcessor $emailProcessor,
        FiscalWebhookSyncProcessor $webhookSyncProcessor,
        FiscalStatusPollProcessor $statusPollProcessor,
        FiscalAuditService $audit,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->queue = $queue;
        $this->emitProcessor = $emitProcessor;
        $this->emailProcessor = $emailProcessor;
        $this->webhookSyncProcessor = $webhookSyncProcessor;
        $this->statusPollProcessor = $statusPollProcessor;
        $this->audit = $audit;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Consume colas fiscal:emit, fiscal:email, fiscal:webhook_sync, fiscal:status_poll, retries')
            ->addOption('once', null, InputOption::VALUE_NONE, 'Procesa un ciclo y termina')
            ->addOption('queue', null, InputOption::VALUE_OPTIONAL, 'Cola específica (default: all)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->queue->isEnabled()) {
            $output->writeln('<error>REDIS_URL no configurado</error>');
            return Command::FAILURE;
        }

        $once = (bool) $input->getOption('once');
        $onlyQueue = $input->getOption('queue');

        do {
            $this->processDueRetries($output);
            $this->queue->touchWorkerHeartbeat();
            $drained = $this->audit->drainQueue(80);
            if ($drained > 0) {
                $output->writeln('<comment>Audit drained: ' . $drained . '</comment>');
            }

            $queues = $this->resolveQueues($onlyQueue);
            $processed = false;

            foreach ($queues as $queue) {
                $job = $this->queue->pop($queue, 1);
                if ($job === null) {
                    continue;
                }
                $processed = true;
                $this->dispatch($queue, $job, $output);
            }

            if ($once && !$processed) {
                break;
            }
        } while (!$once);

        return Command::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function resolveQueues(?string $only): array
    {
        if (is_string($only) && $only !== '') {
            return [$only];
        }
        return [
            FiscalQueueService::QUEUE_EMIT,
            FiscalQueueService::QUEUE_EMAIL,
            FiscalQueueService::QUEUE_WEBHOOK_SYNC,
            FiscalQueueService::QUEUE_STATUS_POLL,
        ];
    }

    /**
     * @param array<string, mixed> $job
     */
    private function dispatch(string $queue, array $job, OutputInterface $output): void
    {
        $uuid = (string) ($job['document_uuid'] ?? '');
        if ($uuid === '') {
            return;
        }

        try {
            switch ($queue) {
                case FiscalQueueService::QUEUE_EMIT:
                    $output->writeln('<info>Emit ' . $uuid . '</info>');
                    $this->emitProcessor->processByUuid($uuid);
                    break;
                case FiscalQueueService::QUEUE_EMAIL:
                    $output->writeln('<info>Email ' . $uuid . '</info>');
                    $this->emailProcessor->processByUuid($uuid);
                    break;
                case FiscalQueueService::QUEUE_WEBHOOK_SYNC:
                    $output->writeln('<info>Webhook sync ' . $uuid . '</info>');
                    $this->webhookSyncProcessor->process($job);
                    break;
                case FiscalQueueService::QUEUE_STATUS_POLL:
                    $output->writeln('<info>Status poll ' . $uuid . '</info>');
                    $this->statusPollProcessor->processByUuid($uuid, (int) ($job['attempt'] ?? 1));
                    break;
            }
        } catch (\Throwable $e) {
            $this->logger->error('fiscal_worker_job_failed', [
                'queue' => $queue,
                'document_uuid' => $uuid,
                'attempt' => $job['attempt'] ?? null,
                'fingerprint' => $job['fingerprint'] ?? null,
                'ruc' => $job['ruc'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $output->writeln('<error>' . $queue . ' ' . $uuid . ': ' . $e->getMessage() . '</error>');
        }
    }

    private function processDueRetries(OutputInterface $output): void
    {
        foreach ($this->queue->dueRetries(FiscalQueueService::QUEUE_RETRY) as $uuid) {
            $output->writeln('<comment>Retry SUNAT: ' . $uuid . '</comment>');
            $this->queue->push(FiscalQueueService::QUEUE_EMIT, ['document_uuid' => $uuid]);
        }
        foreach ($this->queue->dueRetries(FiscalQueueService::QUEUE_PSE_RETRY) as $uuid) {
            $output->writeln('<comment>Retry PSE: ' . $uuid . '</comment>');
            $this->queue->push(FiscalQueueService::QUEUE_EMIT, ['document_uuid' => $uuid]);
        }
        foreach ($this->queue->dueRetries(FiscalQueueService::QUEUE_WEBHOOK_SYNC) as $uuid) {
            $output->writeln('<comment>Retry webhook: ' . $uuid . '</comment>');
            $this->queue->push(FiscalQueueService::QUEUE_WEBHOOK_SYNC, [
                'document_uuid' => $uuid,
                'attempt' => 1,
            ]);
        }
        foreach ($this->queue->dueRetries(FiscalQueueService::QUEUE_STATUS_POLL) as $uuid) {
            $output->writeln('<comment>Retry status poll: ' . $uuid . '</comment>');
            $this->queue->push(FiscalQueueService::QUEUE_STATUS_POLL, [
                'document_uuid' => $uuid,
                'attempt' => 1,
            ]);
        }
    }
}
