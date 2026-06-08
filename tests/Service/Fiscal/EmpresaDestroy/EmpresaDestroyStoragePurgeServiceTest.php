<?php

declare(strict_types=1);

namespace App\Tests\Service\Fiscal\EmpresaDestroy;

use App\Entity\Empresa;
use App\Service\Fiscal\EmpresaDestroy\EmpresaDestroyStoragePurgeService;
use PHPUnit\Framework\TestCase;

class EmpresaDestroyStoragePurgeServiceTest extends TestCase
{
    public function testRemovesCertificateLogoAndTenantStorage(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fiscal_destroy_test_' . uniqid('', true);
        $data = $base . DIRECTORY_SEPARATOR . 'data';
        $storage = $base . DIRECTORY_SEPARATOR . 'fiscal_storage';
        mkdir($data, 0777, true);
        mkdir($storage . DIRECTORY_SEPARATOR . 'demo-co', 0777, true);

        $cert = $data . DIRECTORY_SEPARATOR . '20123456789-cert.pem';
        $logo = $data . DIRECTORY_SEPARATOR . '20123456789-logo.png';
        file_put_contents($cert, 'pem');
        file_put_contents($logo, 'png');
        file_put_contents($storage . DIRECTORY_SEPARATOR . 'demo-co' . DIRECTORY_SEPARATOR . 'doc.xml', '<xml/>');

        $empresa = $this->createMock(Empresa::class);
        $empresa->method('getRuc')->willReturn('20123456789');
        $empresa->method('getTenantSlug')->willReturn('demo-co');
        $empresa->method('getCertificate')->willReturn('20123456789-cert.pem');
        $empresa->method('getLogo')->willReturn('20123456789-logo.png');

        $svc = new EmpresaDestroyStoragePurgeService($data, $storage);
        $result = $svc->purge($empresa);

        self::assertFileDoesNotExist($cert);
        self::assertFileDoesNotExist($logo);
        self::assertDirectoryDoesNotExist($storage . DIRECTORY_SEPARATOR . 'demo-co');
        self::assertNotEmpty($result['removed']);
        self::assertSame([], $result['errors']);

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
