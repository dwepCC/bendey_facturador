<?php

declare(strict_types=1);

namespace App\Tests\Service\Fiscal\EmpresaDestroy;

use App\Service\Fiscal\EmpresaDestroy\EmpresaDestroyPurgeContext;
use App\Service\Fiscal\EmpresaDestroy\EmpresaDestroyRedisPurgeService;
use App\Service\Fiscal\EmpresaDestroy\EmpresaDestroyStoragePurgeService;
use App\Service\Fiscal\FiscalQueueService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class EmpresaDestroyRedisPurgeServiceTest extends TestCase
{
    private ?FiscalQueueService $queue = null;

    protected function setUp(): void
    {
        $redisUrl = getenv('REDIS_URL') ?: 'redis://127.0.0.1:6379';
        $this->queue = new FiscalQueueService($redisUrl);
        if (!$this->queue->isReachable()) {
            $this->markTestSkipped('Redis no disponible para prueba de purge type-aware');
        }
    }

    public function testPurgesListAndZsetWithoutWrongType(): void
    {
        $client = $this->queue->getRedisClient();
        self::assertNotNull($client);

        $ctx = EmpresaDestroyPurgeContext::forOrphanCleanup('20999888777', 'zset-purge-test', 888777);
        $ctx = $ctx->withDocumentData(['aaaaaaaa-bbbb-cccc-dddd-zset000001'], ['20999888777-fp-zset']);

        $listKey = 'fiscal:emit';
        $zsetKey = 'fiscal:webhook_sync';

        $client->del([$listKey]);
        $client->del([$zsetKey]);
        $client->rpush($listKey, [json_encode([
            'document_uuid' => 'aaaaaaaa-bbbb-cccc-dddd-zset000001',
            'ruc' => '20999888777',
            'tenant_slug' => 'zset-purge-test',
        ], JSON_UNESCAPED_UNICODE)]);
        $client->zadd($zsetKey, ['aaaaaaaa-bbbb-cccc-dddd-zset000001' => time() + 3600]);
        $client->set('fiscal:claim:20999888777-fp-zset', '1');

        $svc = new EmpresaDestroyRedisPurgeService($this->queue, new NullLogger());
        $result = $svc->purge($ctx);

        self::assertSame([], $result['errors']);
        self::assertGreaterThan(0, $result['jobs_removed']);
        self::assertSame(0, $svc->countResidues($ctx));
        self::assertNull($client->get('fiscal:claim:20999888777-fp-zset'));
    }
}

class EmpresaDestroyStoragePurgeByContextTest extends TestCase
{
    public function testPurgeByContextWithoutEmpresaEntity(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fiscal_destroy_ctx_' . uniqid('', true);
        $data = $base . DIRECTORY_SEPARATOR . 'data';
        $storage = $base . DIRECTORY_SEPARATOR . 'fiscal_storage';
        mkdir($data, 0777, true);
        mkdir($storage . DIRECTORY_SEPARATOR . 'orphan-co', 0777, true);

        $cert = $data . DIRECTORY_SEPARATOR . '20987654321-cert.pem';
        file_put_contents($cert, 'pem');
        file_put_contents($storage . DIRECTORY_SEPARATOR . 'orphan-co' . DIRECTORY_SEPARATOR . 'doc.xml', '<xml/>');

        $ctx = EmpresaDestroyPurgeContext::forOrphanCleanup('20987654321', 'orphan-co', 123);
        $svc = new EmpresaDestroyStoragePurgeService($data, $storage);
        $result = $svc->purgeByContext($ctx);

        self::assertFileDoesNotExist($cert);
        self::assertDirectoryDoesNotExist($storage . DIRECTORY_SEPARATOR . 'orphan-co');
        self::assertSame([], $result['residues_found']);

        $this->removeDir($base);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
