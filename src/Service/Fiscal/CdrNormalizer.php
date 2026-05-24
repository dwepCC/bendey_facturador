<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use App\Entity\FiscalDocument;
use ZipArchive;

/**
 * ValidaPSE devuelve el campo "cdr" como XML ApplicationResponse en base64,
 * no como ZIP SUNAT. Este helper lo convierte al formato ZIP estándar.
 */
class CdrNormalizer
{
    public static function decodePseCdrField(string $base64, string $filenameBase): ?string
    {
        $decoded = base64_decode($base64, true);
        if ($decoded === false || $decoded === '') {
            return null;
        }

        return self::toSunatZip($decoded, $filenameBase);
    }

    public static function toSunatZip(string $raw, string $filenameBase): ?string
    {
        if ($raw === '') {
            return null;
        }
        if (self::isZipArchive($raw)) {
            return $raw;
        }
        if (!self::isXml($raw)) {
            return null;
        }

        $innerName = 'R-' . $filenameBase . '.xml';

        return self::createZip($innerName, $raw);
    }

    public static function filenameBaseFromDocument(FiscalDocument $doc): string
    {
        $snapshot = json_decode($doc->getSnapshotJson(), true);
        if (!is_array($snapshot)) {
            $snapshot = [];
        }
        $ruc = $snapshot['company_ruc'] ?? ($snapshot['company']['ruc'] ?? '');
        $ruc = preg_replace('/\D/', '', (string) $ruc);

        return sprintf(
            '%s-%s-%s-%s',
            $ruc,
            $doc->getDocumentType(),
            $doc->getSeries(),
            $doc->getNumber()
        );
    }

    public static function isZipArchive(string $content): bool
    {
        return strlen($content) >= 2 && strncmp($content, 'PK', 2) === 0;
    }

    public static function isXml(string $content): bool
    {
        $trim = ltrim($content);

        return strncmp($trim, '<?xml', 5) === 0 || strncmp($trim, '<', 1) === 0;
    }

    private static function createZip(string $innerName, string $content): ?string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cdr_');
        if ($tmp === false) {
            return null;
        }
        $zipPath = $tmp . '.zip';
        @unlink($tmp);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return null;
        }
        $zip->addFromString($innerName, $content);
        $zip->close();

        $bytes = file_get_contents($zipPath);
        @unlink($zipPath);

        return $bytes !== false ? $bytes : null;
    }
}
