<?php

declare(strict_types=1);

namespace App\Service\Fiscal\EmpresaDestroy;

use App\Service\Fiscal\FiscalQueueService;
use Predis\Client;
use Psr\Log\LoggerInterface;

/**
 * Limpia colas y locks Redis del facturador asociados a una empresa.
 * Detecta el tipo real de cada clave (LIST, ZSET, SET, HASH) antes de operar.
 */
class EmpresaDestroyRedisPurgeService
{
    /** Colas y estructuras de retry conocidas del facturador fiscal. */
    private const FISCAL_QUEUE_KEYS = [
        FiscalQueueService::QUEUE_EMIT,
        FiscalQueueService::QUEUE_EMAIL,
        FiscalQueueService::QUEUE_WEBHOOK_SYNC,
        FiscalQueueService::QUEUE_STATUS_POLL,
        FiscalQueueService::QUEUE_STATUS_POLL_RETRY,
        FiscalQueueService::QUEUE_AUDIT,
        FiscalQueueService::QUEUE_RETRY,
        FiscalQueueService::QUEUE_PSE_RETRY,
    ];

    public function __construct(
        private readonly FiscalQueueService $queueService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{
     *     jobs_removed: int,
     *     claims_removed: int,
     *     errors: list<string>,
     *     queues_processed: list<array{key: string, type: string, removed: int}>,
     *     residues_found: int
     * }
     */
    public function purge(EmpresaDestroyPurgeContext $ctx): array
    {
        $client = $this->queueService->getRedisClient();
        if ($client === null) {
            return $this->emptyResult();
        }

        $uuidSet = array_fill_keys($ctx->documentUuids, true);
        $jobsRemoved = 0;
        $errors = [];
        $queuesProcessed = [];

        foreach (self::FISCAL_QUEUE_KEYS as $key) {
            try {
                $processed = $this->purgeKey($client, $key, $ctx, $uuidSet);
                $queuesProcessed[] = $processed;
                $jobsRemoved += $processed['removed'];
            } catch (\Throwable $e) {
                $errors[] = sprintf('%s: %s', $key, $e->getMessage());
                $this->logger->warning('[EmpresaDestroy] Error purgando clave Redis', [
                    'key' => $key,
                    'ruc' => $ctx->ruc,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $claimsRemoved = 0;
        try {
            $claimsRemoved += $this->purgeClaims($client, $ctx);
        } catch (\Throwable $e) {
            $errors[] = 'fiscal:claim:*: ' . $e->getMessage();
        }

        $residuesFound = $this->countResiduesForClient($client, $ctx);

        $this->logger->info('[EmpresaDestroy] Purge Redis completado', [
            'ruc' => $ctx->ruc,
            'tenant_slug' => $ctx->tenantSlug,
            'jobs_removed' => $jobsRemoved,
            'claims_removed' => $claimsRemoved,
            'residues_found' => $residuesFound,
            'queues_processed' => $queuesProcessed,
            'errors' => $errors,
        ]);

        return [
            'jobs_removed' => $jobsRemoved,
            'claims_removed' => $claimsRemoved,
            'errors' => $errors,
            'queues_processed' => $queuesProcessed,
            'residues_found' => $residuesFound,
        ];
    }

    public function countResidues(EmpresaDestroyPurgeContext $ctx): int
    {
        $client = $this->queueService->getRedisClient();
        if ($client === null) {
            return 0;
        }

        return $this->countResiduesForClient($client, $ctx);
    }

    /**
     * @param array<string, true> $uuidSet
     * @return array{key: string, type: string, removed: int}
     */
    private function purgeKey(Client $client, string $key, EmpresaDestroyPurgeContext $ctx, array $uuidSet): array
    {
        $type = strtolower((string) $client->type($key));
        $removed = match ($type) {
            'list' => $this->purgeList($client, $key, $ctx, $uuidSet),
            'zset' => $this->purgeZset($client, $key, $ctx, $uuidSet),
            'set' => $this->purgeSet($client, $key, $ctx, $uuidSet),
            'hash' => $this->purgeHash($client, $key, $ctx, $uuidSet),
            'none' => 0,
            default => 0,
        };

        if ($type !== 'none' && $removed > 0) {
            $this->logger->debug('[EmpresaDestroy] Clave Redis procesada', [
                'key' => $key,
                'type' => $type,
                'removed' => $removed,
                'ruc' => $ctx->ruc,
            ]);
        }

        return ['key' => $key, 'type' => $type, 'removed' => $removed];
    }

    /**
     * @param array<string, true> $uuidSet
     */
    private function purgeList(Client $client, string $key, EmpresaDestroyPurgeContext $ctx, array $uuidSet): int
    {
        $items = $client->lrange($key, 0, -1);
        if (!is_array($items)) {
            return 0;
        }

        $removed = 0;
        foreach ($items as $raw) {
            $raw = (string) $raw;
            if (!$this->payloadMatches($raw, $ctx, $uuidSet)) {
                continue;
            }
            if ((int) $client->lrem($key, 1, $raw) > 0) {
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * @param array<string, true> $uuidSet
     */
    private function purgeZset(Client $client, string $key, EmpresaDestroyPurgeContext $ctx, array $uuidSet): int
    {
        $members = $client->zrange($key, 0, -1);
        if (!is_array($members)) {
            return 0;
        }

        $removed = 0;
        foreach ($members as $member) {
            $member = (string) $member;
            if (!$this->memberMatches($member, $ctx, $uuidSet)) {
                continue;
            }
            if ((int) $client->zrem($key, [$member]) > 0) {
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * @param array<string, true> $uuidSet
     */
    private function purgeSet(Client $client, string $key, EmpresaDestroyPurgeContext $ctx, array $uuidSet): int
    {
        $members = $client->smembers($key);
        if (!is_array($members)) {
            return 0;
        }

        $removed = 0;
        foreach ($members as $member) {
            $member = (string) $member;
            if (!$this->memberMatches($member, $ctx, $uuidSet)) {
                continue;
            }
            if ((int) $client->srem($key, [$member]) > 0) {
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * @param array<string, true> $uuidSet
     */
    private function purgeHash(Client $client, string $key, EmpresaDestroyPurgeContext $ctx, array $uuidSet): int
    {
        $entries = $client->hgetall($key);
        if (!is_array($entries)) {
            return 0;
        }

        $removed = 0;
        foreach ($entries as $field => $value) {
            $fieldStr = (string) $field;
            $valueStr = (string) $value;
            if (
                !$this->memberMatches($fieldStr, $ctx, $uuidSet)
                && !$this->payloadMatches($valueStr, $ctx, $uuidSet)
            ) {
                continue;
            }
            if ((int) $client->hdel($key, [$fieldStr]) > 0) {
                $removed++;
            }
        }

        return $removed;
    }

    private function purgeClaims(Client $client, EmpresaDestroyPurgeContext $ctx): int
    {
        $removed = 0;
        foreach ($ctx->fingerprints as $fp) {
            $fp = trim($fp);
            if ($fp === '') {
                continue;
            }
            $key = 'fiscal:claim:' . $fp;
            if ((int) $client->del([$key]) > 0) {
                $removed++;
            }
        }

        if ($ctx->ruc !== '') {
            $removed += $this->scanDelete($client, 'fiscal:claim:' . $ctx->ruc . '*');
        }

        return $removed;
    }

    private function countResiduesForClient(Client $client, EmpresaDestroyPurgeContext $ctx): int
    {
        $uuidSet = array_fill_keys($ctx->documentUuids, true);
        $count = 0;

        foreach (self::FISCAL_QUEUE_KEYS as $key) {
            $type = strtolower((string) $client->type($key));
            $count += match ($type) {
                'list' => $this->countListResidues($client, $key, $ctx, $uuidSet),
                'zset' => $this->countZsetResidues($client, $key, $ctx, $uuidSet),
                'set' => $this->countSetResidues($client, $key, $ctx, $uuidSet),
                'hash' => $this->countHashResidues($client, $key, $ctx, $uuidSet),
                default => 0,
            };
        }

        if ($ctx->ruc !== '') {
            $count += count($this->scanKeys($client, 'fiscal:claim:' . $ctx->ruc . '*'));
        }

        return $count;
    }

    /**
     * @param array<string, true> $uuidSet
     */
    private function countListResidues(Client $client, string $key, EmpresaDestroyPurgeContext $ctx, array $uuidSet): int
    {
        $items = $client->lrange($key, 0, -1);
        if (!is_array($items)) {
            return 0;
        }
        $count = 0;
        foreach ($items as $raw) {
            if ($this->payloadMatches((string) $raw, $ctx, $uuidSet)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param array<string, true> $uuidSet
     */
    private function countZsetResidues(Client $client, string $key, EmpresaDestroyPurgeContext $ctx, array $uuidSet): int
    {
        $members = $client->zrange($key, 0, -1);
        if (!is_array($members)) {
            return 0;
        }
        $count = 0;
        foreach ($members as $member) {
            if ($this->memberMatches((string) $member, $ctx, $uuidSet)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param array<string, true> $uuidSet
     */
    private function countSetResidues(Client $client, string $key, EmpresaDestroyPurgeContext $ctx, array $uuidSet): int
    {
        $members = $client->smembers($key);
        if (!is_array($members)) {
            return 0;
        }
        $count = 0;
        foreach ($members as $member) {
            if ($this->memberMatches((string) $member, $ctx, $uuidSet)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param array<string, true> $uuidSet
     */
    private function countHashResidues(Client $client, string $key, EmpresaDestroyPurgeContext $ctx, array $uuidSet): int
    {
        $entries = $client->hgetall($key);
        if (!is_array($entries)) {
            return 0;
        }
        $count = 0;
        foreach ($entries as $field => $value) {
            $fieldStr = (string) $field;
            $valueStr = (string) $value;
            if (
                $this->memberMatches($fieldStr, $ctx, $uuidSet)
                || $this->payloadMatches($valueStr, $ctx, $uuidSet)
            ) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param array<string, true> $uuidSet
     */
    private function memberMatches(string $member, EmpresaDestroyPurgeContext $ctx, array $uuidSet): bool
    {
        if ($member !== '' && isset($uuidSet[$member])) {
            return true;
        }

        return $this->payloadMatches($member, $ctx, $uuidSet);
    }

    /**
     * @param array<string, true> $uuidSet
     */
    private function payloadMatches(string $raw, EmpresaDestroyPurgeContext $ctx, array $uuidSet): bool
    {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $uuid = (string) ($decoded['document_uuid'] ?? $decoded['documentUuid'] ?? '');
            if ($uuid !== '' && isset($uuidSet[$uuid])) {
                return true;
            }
            $payloadRuc = preg_replace('/\D/', '', (string) ($decoded['ruc'] ?? ''));
            if ($ctx->ruc !== '' && $payloadRuc === $ctx->ruc) {
                return true;
            }
            $slug = trim((string) ($decoded['tenant_slug'] ?? $decoded['tenantSlug'] ?? ''));
            if ($ctx->tenantSlug !== null && $ctx->tenantSlug !== '' && $slug === $ctx->tenantSlug) {
                return true;
            }
            $tid = $decoded['tenant_id'] ?? $decoded['tenantId'] ?? null;
            if ($ctx->tenantId !== null && $ctx->tenantId > 0 && $tid !== null && (int) $tid === $ctx->tenantId) {
                return true;
            }
            if (isset($decoded['job']) && is_array($decoded['job'])) {
                return $this->payloadMatches(
                    json_encode($decoded['job'], JSON_UNESCAPED_UNICODE),
                    $ctx,
                    $uuidSet
                );
            }
        }

        if ($ctx->ruc !== '' && str_contains($raw, $ctx->ruc)) {
            return true;
        }
        if ($ctx->tenantSlug !== null && $ctx->tenantSlug !== '' && str_contains($raw, $ctx->tenantSlug)) {
            return true;
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function scanKeys(Client $client, string $pattern): array
    {
        $keys = [];
        $cursor = '0';
        do {
            /** @var array{0: string, 1: list<string>}|null $result */
            $result = $client->scan($cursor, ['MATCH' => $pattern, 'COUNT' => 100]);
            if (!is_array($result) || count($result) < 2) {
                break;
            }
            $cursor = (string) $result[0];
            foreach ($result[1] as $key) {
                $keys[] = (string) $key;
            }
        } while ($cursor !== '0');

        return $keys;
    }

    private function scanDelete(Client $client, string $pattern): int
    {
        $keys = $this->scanKeys($client, $pattern);
        if ($keys === []) {
            return 0;
        }

        return (int) $client->del($keys);
    }

    /**
     * @return array{
     *     jobs_removed: int,
     *     claims_removed: int,
     *     errors: list<string>,
     *     queues_processed: list<array{key: string, type: string, removed: int}>,
     *     residues_found: int
     * }
     */
    private function emptyResult(): array
    {
        return [
            'jobs_removed' => 0,
            'claims_removed' => 0,
            'errors' => [],
            'queues_processed' => [],
            'residues_found' => 0,
        ];
    }
}
