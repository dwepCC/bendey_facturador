<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Doctrine\Type\EncryptedStringType;
use App\Security\SecretCipher;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use PHPUnit\Framework\TestCase;

/**
 * Credenciales de empresa (clave SOL, secretos GRE y API de validez) cifradas en reposo.
 */
class SecretCipherTest extends TestCase
{
    private ?string $prevKey = null;

    protected function setUp(): void
    {
        $this->prevKey = $_SERVER['FISCAL_SECRETS_KEY'] ?? null;
        $_SERVER['FISCAL_SECRETS_KEY'] = 'clave-de-prueba-unitaria';
    }

    protected function tearDown(): void
    {
        if ($this->prevKey === null) {
            unset($_SERVER['FISCAL_SECRETS_KEY']);
        } else {
            $_SERVER['FISCAL_SECRETS_KEY'] = $this->prevKey;
        }
    }

    public function testIdaYVueltaSinDejarElSecretoEnClaro(): void
    {
        $enc = SecretCipher::encrypt('claveSolSecreta');
        $this->assertStringStartsWith(SecretCipher::PREFIX, (string) $enc);
        $this->assertStringNotContainsString('claveSolSecreta', (string) $enc);
        $this->assertSame('claveSolSecreta', SecretCipher::decrypt($enc));
        // IV aleatorio: dos cifrados del mismo valor no son iguales.
        $this->assertNotSame($enc, SecretCipher::encrypt('claveSolSecreta'));
        $this->assertLessThanOrEqual(255, strlen((string) SecretCipher::encrypt(str_repeat('x', 150))));
    }

    public function testTextoPlanoHeredadoSeLeeTalCual(): void
    {
        $this->assertSame('PSE', SecretCipher::decrypt('PSE'));
        $this->assertNull(SecretCipher::decrypt(null));
        $this->assertSame('', SecretCipher::encrypt(''));
    }

    public function testNoSeCifraDosVeces(): void
    {
        $enc = SecretCipher::encrypt('x');
        $this->assertSame($enc, SecretCipher::encrypt($enc));
    }

    public function testOtraClaveNoDescifra(): void
    {
        $enc = SecretCipher::encrypt('secreto');
        $_SERVER['FISCAL_SECRETS_KEY'] = 'otra-clave';
        $this->expectException(\RuntimeException::class);
        SecretCipher::decrypt($enc);
    }

    public function testManipuladoNoDescifra(): void
    {
        $enc = (string) SecretCipher::encrypt('secreto');
        $raw = base64_decode(substr($enc, strlen(SecretCipher::PREFIX)), true);
        $raw[strlen($raw) - 1] = chr(ord($raw[strlen($raw) - 1]) ^ 1);
        $this->expectException(\RuntimeException::class);
        SecretCipher::decrypt(SecretCipher::PREFIX . base64_encode($raw));
    }

    public function testSinClaveGuardaEnClaroPeroNoRompe(): void
    {
        unset($_SERVER['FISCAL_SECRETS_KEY']);
        $_ENV['FISCAL_SECRETS_KEY_BACKUP'] = $_ENV['FISCAL_SECRETS_KEY'] ?? null;
        unset($_ENV['FISCAL_SECRETS_KEY']);
        putenv('FISCAL_SECRETS_KEY');
        try {
            $this->assertFalse(SecretCipher::isConfigured());
            $this->assertSame('plano', @SecretCipher::encrypt('plano'));
        } finally {
            if ($_ENV['FISCAL_SECRETS_KEY_BACKUP'] !== null) {
                $_ENV['FISCAL_SECRETS_KEY'] = $_ENV['FISCAL_SECRETS_KEY_BACKUP'];
            }
            unset($_ENV['FISCAL_SECRETS_KEY_BACKUP']);
        }
    }

    public function testTipoDoctrine(): void
    {
        $type = new EncryptedStringType();
        $platform = new MySQLPlatform();
        $db = $type->convertToDatabaseValue('gre-secret', $platform);
        $this->assertStringStartsWith(SecretCipher::PREFIX, (string) $db);
        $this->assertSame('gre-secret', $type->convertToPHPValue($db, $platform));
        $this->assertSame('legacy', $type->convertToPHPValue('legacy', $platform));
        $this->assertNull($type->convertToDatabaseValue(null, $platform));
    }
}
