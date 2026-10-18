<?php

declare(strict_types=1);

namespace App\Doctrine\Type;

use App\Security\SecretCipher;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

/**
 * VARCHAR que se cifra al escribir y se descifra al leer (SecretCipher). La entidad y todo el
 * código que la usa ven el valor en claro; en la base solo queda "enc:v1:…".
 */
final class EncryptedStringType extends StringType
{
    public const NAME = 'encrypted_string';

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $value === null ? null : SecretCipher::encrypt((string) $value);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $value === null ? null : SecretCipher::decrypt((string) $value);
    }
}
