<?php

declare(strict_types=1);

namespace App\Twig;

use Greenter\Report\Filter\ImageFilter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Evita fallos de substr() en Greenter ImageFilter cuando logo/QR es null o vacío.
 */
final class SafeImageTwigExtension extends AbstractExtension
{
    /** PNG 1×1 transparente para wkhtmltopdf cuando no hay imagen. */
    private const TRANSPARENT_PNG_DATA_URI =
        'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    public function getFilters(): array
    {
        return [
            new TwigFilter('image_b64', [$this, 'toBase64']),
        ];
    }

    public function toBase64(mixed $image, string $mime = ''): string
    {
        if (!is_string($image) || $image === '') {
            return self::TRANSPARENT_PNG_DATA_URI;
        }

        $result = (new ImageFilter())->toBase64($image, $mime);

        return is_string($result) && $result !== '' ? $result : self::TRANSPARENT_PNG_DATA_URI;
    }
}
