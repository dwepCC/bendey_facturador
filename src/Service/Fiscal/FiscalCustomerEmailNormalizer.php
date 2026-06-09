<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use Greenter\Model\Client\Client;
use Greenter\Model\Company\Company;
use Greenter\Model\DocumentInterface;
use Greenter\Model\Sale\BaseSale;

/**
 * Normalización de emails de cliente para PDF (placeholder) y envío (null = no enviar).
 */
final class FiscalCustomerEmailNormalizer
{
    /** Placeholder interno para PDF; no debe usarse en envíos reales. */
    public const PLACEHOLDER = 'no-email@tukifac.local';

    public const STATUS_NOT_AVAILABLE = 'email_not_available';

    public static function normalize(mixed $email): ?string
    {
        if (!is_string($email)) {
            return null;
        }
        $email = trim($email);

        return $email === '' ? null : $email;
    }

    public static function isDeliverable(?string $email): bool
    {
        $email = self::normalize($email);
        if ($email === null) {
            return false;
        }
        if (strcasecmp($email, self::PLACEHOLDER) === 0) {
            return false;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /** Email seguro para templates PDF (nunca null). */
    public static function forPdfDisplay(?string $email): string
    {
        $normalized = self::normalize($email);

        return $normalized ?? self::PLACEHOLDER;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function extractFromPayload(array $payload, array $snapshot): ?string
    {
        if (isset($payload['customer_email'])) {
            $fromPayload = self::normalize($payload['customer_email']);
            if ($fromPayload !== null) {
                return $fromPayload;
            }
        }

        return self::extractFromSnapshotArray($snapshot);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function extractFromSnapshotArray(array $data): ?string
    {
        if (isset($data['customer_email'])) {
            $email = self::normalize($data['customer_email']);
            if ($email !== null) {
                return $email;
            }
        }
        foreach (['customer', 'client'] as $key) {
            if (!isset($data[$key]) || !is_array($data[$key])) {
                continue;
            }
            $email = self::normalize($data[$key]['email'] ?? null);
            if ($email !== null) {
                return $email;
            }
        }

        return null;
    }

    public static function extractFromSnapshotJson(string $snapshotJson): ?string
    {
        $data = json_decode($snapshotJson, true);

        return is_array($data) ? self::extractFromSnapshotArray($data) : null;
    }

    /** Evita filtros Twig (slice/substr) sobre null en PDF. */
    public static function applyPdfSafeEmails(DocumentInterface $document): void
    {
        if ($document instanceof BaseSale) {
            self::applyPdfSafeClient($document->getClient());
            self::applyPdfSafeCompany($document->getCompany());
            // Solo factura/boleta (Invoice) tiene vendedor; NC/ND (Note) no.
            if (method_exists($document, 'getSeller')) {
                self::applyPdfSafeClient($document->getSeller());
            }
        } elseif (method_exists($document, 'getClient')) {
            self::applyPdfSafeClient($document->getClient());
        }
        if (method_exists($document, 'getCompany')) {
            self::applyPdfSafeCompany($document->getCompany());
        }
    }

    private static function applyPdfSafeClient(?Client $client): void
    {
        if ($client === null) {
            return;
        }
        $client->setEmail(self::forPdfDisplay($client->getEmail()));
    }

    private static function applyPdfSafeCompany(?Company $company): void
    {
        if ($company === null) {
            return;
        }
        $company->setEmail(self::forPdfDisplay($company->getEmail()));
    }
}
