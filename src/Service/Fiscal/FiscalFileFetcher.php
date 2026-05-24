<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

/**
 * Lee archivos fiscales desde storage local o URL pública (S3/R2/CDN).
 */
class FiscalFileFetcher
{
    private string $localStoragePath;

    public function __construct(string $localStoragePath)
    {
        $this->localStoragePath = rtrim($localStoragePath, '/\\');
    }

    /**
     * @return array{content: string, mime: string}|null
     */
    public function fetch(?string $publicUrl): ?array
    {
        if ($publicUrl === null || trim($publicUrl) === '') {
            return null;
        }
        $local = $this->resolveLocalPath($publicUrl);
        if ($local !== null && is_readable($local)) {
            $content = file_get_contents($local);
            return $content !== false ? ['content' => $content, 'mime' => $this->mimeForPath($local)] : null;
        }
        $content = $this->fetchRemote($publicUrl);
        if ($content === null) {
            return null;
        }
        return ['content' => $content, 'mime' => $this->mimeForPath($publicUrl)];
    }

    private function resolveLocalPath(string $url): ?string
    {
        $parts = parse_url($url);
        if (!isset($parts['path'])) {
            return null;
        }
        $path = ltrim((string) $parts['path'], '/');
        if (strpos($path, 'fiscal-files/') === 0) {
            $path = substr($path, strlen('fiscal-files/'));
        }
        $full = $this->localStoragePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        return is_file($full) ? $full : null;
    }

    private function fetchRemote(string $url): ?string
    {
        if (!preg_match('#^https?://#i', $url)) {
            return null;
        }
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code >= 400) {
            return null;
        }
        return (string) $body;
    }

    private function mimeForPath(string $path): string
    {
        $ext = strtolower(pathinfo(parse_url($path, PHP_URL_PATH) ?: $path, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'pdf':
                return 'application/pdf';
            case 'xml':
                return 'application/xml';
            case 'zip':
                return 'application/zip';
            default:
                return 'application/octet-stream';
        }
    }
}
