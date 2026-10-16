<?php

declare(strict_types=1);

namespace App\Tests\Service\Fiscal;

use App\Service\Fiscal\FiscalQueueService;
use PHPUnit\Framework\TestCase;

/**
 * Incidente 2026-10-15: el reintento del webhook hacía ZADD sobre la LISTA `fiscal:webhook_sync`
 * y el siguiente RPUSH/BLPOP fallaba con WRONGTYPE. Los reintentos van ahora a su propio ZSET y
 * guardan el número de intento en el miembro.
 */
class FiscalWebhookRetryMemberTest extends TestCase
{
    public function testLaColaDeReintentosNoEsLaColaDeTrabajos(): void
    {
        $this->assertNotSame(FiscalQueueService::QUEUE_WEBHOOK_SYNC, FiscalQueueService::QUEUE_WEBHOOK_SYNC_RETRY);
    }

    public function testIdaYVuelta(): void
    {
        $member = FiscalQueueService::webhookRetryMember('3aead5f6-40b3-4985-9f3e-746359ab1988', 3);
        $this->assertSame('3aead5f6-40b3-4985-9f3e-746359ab1988#3', $member);
        $this->assertSame(['3aead5f6-40b3-4985-9f3e-746359ab1988', 3], FiscalQueueService::parseWebhookRetryMember($member));
    }

    public function testMiembroDelFormatoAnteriorEsElPrimerIntento(): void
    {
        $this->assertSame(['3aead5f6-40b3-4985-9f3e-746359ab1988', 1], FiscalQueueService::parseWebhookRetryMember('3aead5f6-40b3-4985-9f3e-746359ab1988'));
    }

    public function testIntentoInvalidoSeNormalizaAUno(): void
    {
        $this->assertSame(['abc', 1], FiscalQueueService::parseWebhookRetryMember('abc#x'));
        $this->assertSame('abc#1', FiscalQueueService::webhookRetryMember('abc', 0));
    }
}
