<?php

declare(strict_types=1);

namespace App\Tests\Service\Gre;

use App\Entity\Empresa;
use App\Service\ConfigProviderInterface;
use App\Service\Gre\GreEndpointResolver;
use App\Service\Gre\GreOAuthCredentialResolver;
use PHPUnit\Framework\TestCase;

class GreOAuthCredentialResolverTest extends TestCase
{
    public function testUsesEmpresaCredentialsWhenPresent(): void
    {
        $empresa = (new Empresa())->setRuc('20123456789')->setAmbiente('pruebas');
        $empresa->setGreClientId('my-id');
        $empresa->setGreClientSecret('my-secret');

        $config = $this->createMock(ConfigProviderInterface::class);
        $resolved = GreOAuthCredentialResolver::resolve($empresa, $config);

        self::assertSame('my-id', $resolved['client_id']);
        self::assertSame('empresa', $resolved['source']);
    }

    public function testFallbackNubefactInPruebas(): void
    {
        $empresa = (new Empresa())->setRuc('20123456789')->setAmbiente('pruebas');

        $config = $this->createMock(ConfigProviderInterface::class);
        $config->method('get')->willReturnMap([
            ['CLIENT_ID', 'test-id'],
            ['CLIENT_SECRET', 'test-secret'],
        ]);

        $resolved = GreOAuthCredentialResolver::resolve($empresa, $config);
        self::assertSame('test-id', $resolved['client_id']);
        self::assertSame('nubefact_test_fallback', $resolved['source']);
    }

    public function testProductionRequiresEmpresaOAuth(): void
    {
        $empresa = (new Empresa())->setRuc('20123456789')->setAmbiente('produccion');
        $config = $this->createMock(ConfigProviderInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        GreOAuthCredentialResolver::resolve($empresa, $config);
    }

    public function testEndpointResolverProduction(): void
    {
        $config = $this->createMock(ConfigProviderInterface::class);
        $config->method('get')->willReturn('');
        $endpoints = GreEndpointResolver::resolveForAmbiente('produccion', $config);
        self::assertStringContainsString('api-seguridad.sunat.gob.pe', $endpoints['auth']);
        self::assertStringContainsString('api-cpe.sunat.gob.pe', $endpoints['cpe']);
    }
}
