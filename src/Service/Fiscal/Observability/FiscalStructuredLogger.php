<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Observability;

use Psr\Log\LoggerInterface;

/**
 * Logging estructurado JSON unificado para eventos fiscales.
 */
class FiscalStructuredLogger
{
    private LoggerInterface $logger;
    private FiscalSecretRedactor $redactor;

    public function __construct(LoggerInterface $logger, FiscalSecretRedactor $redactor)
    {
        $this->logger = $logger;
        $this->redactor = $redactor;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function log(string $event, array $context = []): void
    {
        $payload = array_merge(['event' => $event, 'ts' => (new \DateTimeImmutable())->format(DATE_ATOM)], $context);
        $payload = $this->redactor->redactArray($payload);
        $this->logger->info('fiscal_observability', $payload);
    }
}
