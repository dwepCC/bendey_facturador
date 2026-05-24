<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Provider;

use Greenter\Model\Response\CdrResponse;

/**
 * Clasifica respuesta CDR SUNAT según Greenter (CdrResponse::isAccepted).
 * Código 0 = aceptado; >= 4000 = aceptado con observaciones; otro = rechazado.
 */
final class SunatCdrClassifier
{
    /**
     * @return array{success: bool, rejected: bool, observed: bool, code: ?string, message: ?string, notes: array<int, string>}
     */
    public static function fromCdrResponse(?CdrResponse $cdr): array
    {
        if ($cdr === null) {
            return [
                'success' => false,
                'rejected' => false,
                'observed' => false,
                'code' => null,
                'message' => null,
                'notes' => [],
            ];
        }

        $code = $cdr->getCode() !== null ? (string) $cdr->getCode() : null;
        $message = $cdr->getDescription() !== null ? (string) $cdr->getDescription() : null;
        $notes = self::normalizeNotes($cdr->getNotes());

        if (!$cdr->isAccepted()) {
            return [
                'success' => false,
                'rejected' => true,
                'observed' => false,
                'code' => $code,
                'message' => $message,
                'notes' => $notes,
            ];
        }

        $observed = self::isObservedCode($code) || $notes !== [];

        return [
            'success' => true,
            'rejected' => false,
            'observed' => $observed,
            'code' => $code,
            'message' => self::buildMessage($message, $notes),
            'notes' => $notes,
        ];
    }

    public static function isObservedCode(?string $code): bool
    {
        if ($code === null || $code === '') {
            return false;
        }
        $n = (int) $code;

        return $n >= 4000;
    }

    /**
     * @param mixed $raw
     * @return array<int, string>
     */
    private static function normalizeNotes($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $note) {
            if (is_string($note) && trim($note) !== '') {
                $out[] = trim($note);
            }
        }

        return $out;
    }

    /**
     * @param array<int, string> $notes
     */
    private static function buildMessage(?string $description, array $notes): ?string
    {
        $description = $description !== null ? trim($description) : '';
        if ($notes === []) {
            return $description !== '' ? $description : null;
        }
        $joined = implode(' | ', $notes);
        if ($description === '') {
            return $joined;
        }

        return $description . ' — ' . $joined;
    }
}
