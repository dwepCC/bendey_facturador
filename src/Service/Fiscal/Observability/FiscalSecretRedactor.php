<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Observability;

/**
 * Redacta secretos de mensajes de log/audit.
 */
final class FiscalSecretRedactor
{
    private const PATTERNS = [
        '/SOL_PASS["\']?\s*[:=]\s*["\']?[^"\',\s}]+/i',
        '/SOL_USER["\']?\s*[:=]\s*["\']?[^"\',\s}]+/i',
        '/pse_token["\']?\s*[:=]\s*["\']?[^"\',\s}]+/i',
        '/pse_pass(?:word)?["\']?\s*[:=]\s*["\']?[^"\',\s}]+/i',
        '/Bearer\s+[A-Za-z0-9._\-]+/i',
        '/Authorization["\']?\s*[:=]\s*["\']?[^"\',\s}]+/i',
        '/certificate["\']?\s*[:=]\s*["\']?[A-Za-z0-9+\/=]{40,}/i',
    ];

    public function redact(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }
        foreach (self::PATTERNS as $pattern) {
            $text = preg_replace($pattern, '[REDACTED]', $text) ?? $text;
        }
        return $text;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function redactArray(array $data): array
    {
        $secretKeys = ['sol_pass', 'sol_user', 'pse_token', 'pse_pass', 'pse_password', 'certificate', 'token', 'password', 'authorization'];
        foreach ($data as $k => $v) {
            $lk = strtolower((string) $k);
            if (in_array($lk, $secretKeys, true)) {
                $data[$k] = '[REDACTED]';
            } elseif (is_string($v)) {
                $data[$k] = $this->redact($v);
            } elseif (is_array($v)) {
                $data[$k] = $this->redactArray($v);
            }
        }
        return $data;
    }
}
