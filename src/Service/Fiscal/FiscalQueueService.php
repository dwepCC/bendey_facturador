<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use Predis\Client;

/**
 * Colas Redis fiscales dedicadas (separadas de billing SaaS ERP).
 */
class FiscalQueueService
{
    public const QUEUE_EMIT = 'fiscal:emit';
    public const QUEUE_RETRY = 'fiscal:retry';
    public const QUEUE_EMAIL = 'fiscal:email';
    public const QUEUE_WEBHOOK_SYNC = 'fiscal:webhook_sync';
    public const QUEUE_PSE_RETRY = 'fiscal:pse_retry';
    public const QUEUE_STATUS_POLL = 'fiscal:status_poll';
    /** ZSET de reintentos programados (no mezclar con QUEUE_STATUS_POLL que es LIST). */
    public const QUEUE_STATUS_POLL_RETRY = 'fiscal:status_poll:retry';
    public const QUEUE_AUDIT = 'fiscal:audit';
    private const KEY_WORKER_HEARTBEAT = 'fiscal:worker:heartbeat';

    private const PREFIX_CLAIM = 'fiscal:claim:';

    private ?Client $client;
    private ?FiscalJobDispatcher $jobDispatcher = null;

    public function __construct(?string $redisUrl)
    {
        if ($redisUrl === null || trim($redisUrl) === '') {
            $this->client = null;
            return;
        }
        $this->client = new Client($redisUrl);
    }

    public function isEnabled(): bool
    {
        return $this->client !== null;
    }

    /** Redis configurado y respondiendo (ping). */
    public function isReachable(): bool
    {
        if ($this->client === null) {
            return false;
        }
        try {
            $this->client->ping();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @template T
     * @param T $fallback
     * @param callable(): T $fn
     * @return T
     */
    private function safeRead($fallback, callable $fn)
    {
        if ($this->client === null) {
            return $fallback;
        }
        try {
            return $fn();
        } catch (\Throwable) {
            return $fallback;
        }
    }

    public function tryClaimEmit(string $fingerprint, int $ttlSeconds = 120): bool
    {
        if ($this->client === null) {
            return true;
        }
        $key = self::PREFIX_CLAIM . $fingerprint;
        $ok = $this->client->set($key, '1', 'EX', $ttlSeconds, 'NX');
        return $ok !== null;
    }

    public function releaseClaim(string $fingerprint): void
    {
        if ($this->client === null) {
            return;
        }
        $this->client->del([self::PREFIX_CLAIM . $fingerprint]);
    }

    public function setJobDispatcher(FiscalJobDispatcher $dispatcher): void
    {
        $this->jobDispatcher = $dispatcher;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function push(string $queue, array $payload): void
    {
        if ($this->client !== null) {
            $this->pushToRedis($queue, $payload);
            return;
        }
        if ($this->jobDispatcher !== null) {
            $this->jobDispatcher->dispatchSync($queue, $payload);
            return;
        }
        throw new \RuntimeException('Redis no configurado; defina REDIS_URL');
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function pushToRedis(string $queue, array $payload): void
    {
        if ($this->client === null) {
            throw new \RuntimeException('Redis no configurado; defina REDIS_URL');
        }
        $this->client->rpush($queue, [json_encode($payload, JSON_UNESCAPED_UNICODE)]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function pop(string $queue, int $timeoutSeconds = 5): ?array
    {
        if ($this->client === null) {
            return null;
        }
        $item = $this->client->blpop([$queue], $timeoutSeconds);
        if ($item === null || !isset($item[1])) {
            return null;
        }
        $decoded = json_decode((string) $item[1], true);
        return is_array($decoded) ? $decoded : null;
    }

    public function scheduleRetry(string $documentUuid, int $delaySeconds, string $queue = self::QUEUE_RETRY): void
    {
        if ($this->client === null) {
            return;
        }
        $score = time() + $delaySeconds;
        $this->client->zadd($queue, [$documentUuid => $score]);
    }

    /**
     * @return string[]
     */
    public function dueRetries(string $queue = self::QUEUE_RETRY, int $limit = 20): array
    {
        if ($this->client === null) {
            return [];
        }
        $now = time();
        $items = $this->client->zrangebyscore($queue, '-inf', (string) $now, ['LIMIT' => [0, $limit]]);
        if (!is_array($items)) {
            return [];
        }
        foreach ($items as $uuid) {
            $this->client->zrem($queue, [$uuid]);
        }
        return array_map('strval', $items);
    }

    public function queueLength(string $queue): int
    {
        return $this->safeRead(0, fn () => (int) $this->client->llen($queue));
    }

    public function pushAudit(string $json): void
    {
        if ($this->client === null) {
            return;
        }
        try {
            $this->client->rpush(self::QUEUE_AUDIT, [$json]);
        } catch (\Throwable) {
        }
    }

    public function popAuditRaw(): ?string
    {
        if ($this->client === null) {
            return null;
        }
        $item = $this->client->blpop([self::QUEUE_AUDIT], 1);
        if ($item === null || !isset($item[1])) {
            return null;
        }
        return (string) $item[1];
    }

    public function touchWorkerHeartbeat(int $ttlSeconds = 60): void
    {
        if ($this->client === null) {
            return;
        }
        $this->client->setex(self::KEY_WORKER_HEARTBEAT, $ttlSeconds, (string) time());
    }

    public function workerHeartbeatAge(): ?int
    {
        return $this->safeRead(null, function () {
            $val = $this->client->get(self::KEY_WORKER_HEARTBEAT);
            if ($val === null) {
                return null;
            }
            return time() - (int) $val;
        });
    }

    public function scheduledRetryCount(string $queue = self::QUEUE_RETRY): int
    {
        return $this->safeRead(0, fn () => (int) $this->client->zcard($queue));
    }

    /** Cliente Redis interno (purge administrativo). */
    public function getRedisClient(): ?Client
    {
        return $this->client;
    }
}
