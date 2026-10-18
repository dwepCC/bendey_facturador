<?php

declare(strict_types=1);

namespace App\Tests\Service\Fiscal;

use App\Entity\Empresa;
use App\Repository\EmpresaRepository;
use App\Service\ConfigProviderInterface;
use App\Service\EmpresasService;
use App\Service\Fiscal\FiscalCompanySyncService;
use App\Service\Fiscal\Observability\FiscalSecretRedactor;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Caso comercializadoradecalzadosadri (RUC 20614224682, PSE): el Panel Central no podía registrar el
 * usuario SOL secundario ni las credenciales API, y aunque llegaran, el facturador las ignoraba en
 * modo PSE (quedaba la clave SOL de relleno «PSE»). Ahora valen para cualquier modo.
 */
class FiscalCompanySyncCredentialsTest extends TestCase
{
    private const RUC = '20614224682';

    public function testEnPseSeGuardanSolGreYApiDeValidez(): void
    {
        $captured = null;
        $empresas = $this->createMock(EmpresasService::class);
        $empresas->expects($this->once())->method('addOrUpdateEmpresas')
            ->willReturnCallback(function (array $arg) use (&$captured): void {
                $captured = $arg;
            });

        $this->service($empresas, $this->empresaPse())->sync([
            'ruc' => self::RUC,
            'send_mode' => 'pse',
            'provider' => 'validapse',
            'ambiente' => 'produccion',
            'SOL_USER' => self::RUC . 'CONSULTA1',
            'SOL_PASS' => 'claveSol',
            'gre_client_id' => 'gre-id',
            'gre_client_secret' => 'gre-secret',
            'cpe_api_client_id' => 'cpe-id',
            'cpe_api_client_secret' => 'cpe-secret',
        ]);

        $entry = $captured[self::RUC];
        $this->assertSame(self::RUC . 'CONSULTA1', $entry['SOL_USER']);
        $this->assertSame('claveSol', $entry['SOL_PASS']);
        $this->assertSame('gre-id', $entry['gre_client_id']);
        $this->assertSame('gre-secret', $entry['gre_client_secret']);
        $this->assertSame('cpe-id', $entry['cpe_api_client_id']);
        $this->assertSame('cpe-secret', $entry['cpe_api_client_secret']);
        $this->assertSame('pse', $entry['send_mode']);
    }

    public function testSinCredencialesNuevasNoSePisanLasGuardadas(): void
    {
        $captured = null;
        $empresas = $this->createMock(EmpresasService::class);
        $empresas->method('addOrUpdateEmpresas')->willReturnCallback(function (array $arg) use (&$captured): void {
            $captured = $arg;
        });

        $this->service($empresas, $this->empresaPse())->sync(['ruc' => self::RUC, 'send_mode' => 'pse', 'ambiente' => 'produccion']);

        $entry = $captured[self::RUC];
        foreach (['SOL_USER', 'SOL_PASS', 'gre_client_id', 'gre_client_secret', 'cpe_api_client_id', 'cpe_api_client_secret'] as $k) {
            $this->assertArrayNotHasKey($k, $entry, $k . ' vacío = no tocar');
        }
    }

    public function testEstadoNoDaPorConfiguradoElRellenoPseNiDevuelveSecretos(): void
    {
        $e = $this->empresaPse();
        $service = $this->service($this->createMock(EmpresasService::class), $e);

        $status = $service->buildStatus($e);
        $this->assertFalse($status['sol_configured']);
        $this->assertNull($status['sol_user']);
        $this->assertFalse($status['cpe_api_configured']);

        $e->setSolUser(self::RUC . 'CONSULTA1');
        $e->setSolPass('claveSol');
        $e->setCpeApiClientId('cpe-id');
        $e->setCpeApiClientSecret('cpe-secret');
        $status = $service->buildStatus($e);
        $this->assertTrue($status['sol_configured']);
        $this->assertSame(self::RUC . 'CONSULTA1', $status['sol_user']);
        $this->assertTrue($status['cpe_api_configured']);
        $json = (string) json_encode($status);
        foreach (['claveSol', 'cpe-secret', 'token-secreto-pse'] as $secret) {
            $this->assertStringNotContainsString($secret, $json);
        }
    }

    public function testRedactorTapaLosClientSecret(): void
    {
        $r = new FiscalSecretRedactor();
        $this->assertStringNotContainsString('cpe-secret', (string) $r->redact('{"cpe_api_client_secret":"cpe-secret"}'));
        $this->assertStringNotContainsString('gre-secret', (string) $r->redact('gre_client_secret=gre-secret'));
        $this->assertSame('[REDACTED]', $r->redactArray(['cpe_api_client_secret' => 'x'])['cpe_api_client_secret']);
    }

    private function service(EmpresasService $empresas, Empresa $existing): FiscalCompanySyncService
    {
        $repo = $this->createMock(EmpresaRepository::class);
        $repo->method('findByRuc')->willReturn($existing);

        return new FiscalCompanySyncService(
            $empresas,
            $repo,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(ConfigProviderInterface::class)
        );
    }

    private function empresaPse(): Empresa
    {
        $e = new Empresa();
        $e->setRuc(self::RUC);
        $e->setSolUser('PSE');
        $e->setSolPass('PSE');
        $e->setSendMode('pse');
        $e->setProvider('validapse');
        $e->setAmbiente('produccion');
        $e->setPseUser('usuario');
        $e->setPseToken('token-secreto-pse');
        $e->setPseBaseUrl('https://app.validapse.com');

        return $e;
    }
}
