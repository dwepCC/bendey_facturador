<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use App\Entity\FiscalDocument;
use App\Entity\FiscalWebhookEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Webhook al ERP con firma HMAC-SHA256 y auditoría en BD.
 */
class FiscalWebhookService
{
    private ?string $erpWebhookUrl;
    private ?string $erpWebhookKey;
    private LoggerInterface $logger;
    private EntityManagerInterface $em;

    public function __construct(
        ?string $erpWebhookUrl,
        ?string $erpWebhookKey,
        LoggerInterface $logger,
        EntityManagerInterface $em
    ) {
        $this->erpWebhookUrl = $erpWebhookUrl !== '' && $erpWebhookUrl !== null ? $erpWebhookUrl : null;
        $this->erpWebhookKey = $erpWebhookKey !== '' && $erpWebhookKey !== null ? $erpWebhookKey : null;
        $this->logger = $logger;
        $this->em = $em;
    }

    public function notifyStatus(FiscalDocument $doc): void
    {
        if ($this->erpWebhookUrl === null) {
            return;
        }

        $payload = [
            'tenant_id' => $doc->getTenantId(),
            'tenant_slug' => $doc->getTenantSlug(),
            'sale_id' => $doc->getSaleId(),
            'document_uuid' => $doc->getDocumentUuid(),
            'fiscal_fingerprint' => $doc->getFiscalFingerprint(),
            'status' => $doc->getStatus(),
            'provider' => $doc->getProvider(),
            'send_mode' => $doc->getSendMode(),
            'xml_url' => $doc->getXmlUrl(),
            'xml_signed_url' => $doc->getXmlSignedUrl(),
            'unsigned_xml_url' => $doc->getUnsignedXmlUrl(),
            'cdr_url' => $doc->getCdrUrl(),
            'pdf_url' => $doc->getPdfUrl(),
            'hash' => $doc->getHash(),
            'ticket' => $doc->getTicket(),
            'sunat_code' => $doc->getSunatCode(),
            'sunat_message' => $doc->getSunatMessage(),
            'sunat_cdr_notes' => $this->extractCdrNotes($doc),
            'retry_count' => $doc->getRetryCount(),
            'event_id' => $this->eventId($doc),
            'timestamp' => time(),
        ];

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            throw new \RuntimeException('payload webhook inválido');
        }

        $eventKey = (string) $payload['event_id'];
        $log = new FiscalWebhookEvent();
        $log->setEventKey($eventKey);
        $log->setDocumentUuid($doc->getDocumentUuid());
        $log->setPayloadHash(hash('sha256', $body));

        $headers = ['Content-Type: application/json'];
        if ($this->erpWebhookKey !== null) {
            $sig = hash_hmac('sha256', $body, $this->erpWebhookKey);
            $headers[] = 'X-Internal-Key: ' . $this->erpWebhookKey;
            $headers[] = 'X-Fiscal-Signature: sha256=' . $sig;
            $headers[] = 'X-Fiscal-Event-Id: ' . $eventKey;
        }

        $ch = curl_init($this->erpWebhookUrl);
        if ($ch === false) {
            throw new \RuntimeException('curl_init failed');
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $respBody = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $log->setHttpStatus($code);
        $log->setResponseBody(is_string($respBody) ? substr($respBody, 0, 2000) : null);
        if ($respBody !== false && $code < 400) {
            $log->setDeliveredAt(new \DateTimeImmutable());
        }
        try {
            $this->em->persist($log);
            $this->em->flush();
        } catch (\Throwable $e) {
            // duplicate event_key = idempotent webhook log
            $this->em->clear(FiscalWebhookEvent::class);
        }

        if ($respBody === false || $code >= 400) {
            $this->logger->warning('fiscal_webhook_failed', [
                'status' => $code,
                'uuid' => $doc->getDocumentUuid(),
            ]);
            throw new \RuntimeException('webhook HTTP ' . $code);
        }
    }

    private function eventId(FiscalDocument $doc): string
    {
        return hash('sha256', implode('|', [
            $doc->getDocumentUuid(),
            $doc->getStatus(),
            (string) ($doc->getUpdatedAt()->getTimestamp()),
            (string) ($doc->getSunatCode() ?? ''),
        ]));
    }

    /**
     * @return array<int, string>
     */
    private function extractCdrNotes(FiscalDocument $doc): array
    {
        $raw = $doc->getPseResponseJson();
        if ($raw === null || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        $notes = $decoded['cdr_notes'] ?? [];
        if (!is_array($notes)) {
            return [];
        }
        $out = [];
        foreach ($notes as $note) {
            if (is_string($note) && trim($note) !== '') {
                $out[] = trim($note);
            }
        }

        return $out;
    }
}

