<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

/**
 * Interpreta fechas YYYY-MM-DD del dashboard fiscal en hora Perú.
 * El fin de rango es exclusivo (inicio del día siguiente) para incluir todo el día "hasta".
 */
final class FiscalDateRangeParser
{
    public const TZ = 'America/Lima';

    public static function rangeStart(?string $date): ?\DateTimeImmutable
    {
        $date = self::normalizeDateOnly($date);
        if ($date === null) {
            return null;
        }

        return new \DateTimeImmutable($date . ' 00:00:00', new \DateTimeZone(self::TZ));
    }

    /** Límite exclusivo: registros con createdAt < este instante (inicio del día siguiente al "hasta"). */
    public static function rangeEndExclusive(?string $date): ?\DateTimeImmutable
    {
        $start = self::rangeStart($date);
        if ($start === null) {
            return null;
        }

        return $start->modify('+1 day');
    }

    private static function normalizeDateOnly(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $str = trim($value);
        if ($str === '') {
            return null;
        }
        if (str_contains($str, 'T')) {
            $str = substr($str, 0, 10);
        } elseif (str_contains($str, ' ')) {
            $str = substr($str, 0, 10);
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
            return null;
        }

        return $str;
    }
}
