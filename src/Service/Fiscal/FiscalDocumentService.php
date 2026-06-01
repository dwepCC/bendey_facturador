<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use App\Entity\FiscalDocument;
use App\Repository\FiscalDocumentRepository;
use App\Service\Fiscal\Observability\FiscalAuditService;
use App\Entity\FiscalAuditLog;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Crea documentos fiscales idempotentes y los encola.
 * El ERP envía solo tenant_id + ruc + documento; modo fiscal se resuelve en emisión (empresa SSOT).
 */
class FiscalDocumentService
{
    public const SCHEMA_VERSION = '1.0';
    public const SNAPSHOT_VERSION = 1;
    public const GREENTER_VERSION = '5.2';

    private EntityManagerInterface $em;
    private FiscalDocumentRepository $repo;
    private FiscalQueueService $queue;
    private LoggerInterface $logger;
    private ?FiscalAuditService $audit;

    public function __construct(
        EntityManagerInterface $em,
        FiscalDocumentRepository $repo,
        FiscalQueueService $queue,
        LoggerInterface $logger,
        ?FiscalAuditService $audit = null
    ) {
        $this->em = $em;
        $this->repo = $repo;
        $this->queue = $queue;
        $this->logger = $logger;
        $this->audit = $audit;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function enqueue(array $payload): FiscalDocument
    {
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        $tenantSlug = (string) ($payload['tenant_slug'] ?? '');
        $saleId = (int) ($payload['sale_id'] ?? 0);
        $ruc = trim((string) ($payload['ruc'] ?? ''));
        if ($tenantId <= 0 || $tenantSlug === '' || $saleId <= 0) {
            throw new \InvalidArgumentException('tenant_id, tenant_slug y sale_id son obligatorios');
        }
        if ($ruc === '') {
            throw new \InvalidArgumentException('ruc es obligatorio');
        }

        $snapshot = $payload['document'] ?? $payload['snapshot'] ?? $payload['snapshot_json'] ?? null;
        if (!is_array($snapshot)) {
            throw new \InvalidArgumentException('document JSON requerido');
        }
        unset($snapshot['_meta']);

        $docType = (string) ($snapshot['tipoDoc'] ?? $payload['document_type'] ?? '03');
        $series = (string) ($snapshot['serie'] ?? $payload['series'] ?? '');
        $number = (string) ($snapshot['correlativo'] ?? $payload['number'] ?? '');

        $fingerprint = FiscalFingerprint::build($tenantId, $docType, $series, $number, $saleId);
        $automatic = $payload['automatic_send'] ?? true;

        $existing = $this->repo->findOneBy(['fiscalFingerprint' => $fingerprint]);
        if ($existing !== null) {
            if ($existing->getStatus() === FiscalDocument::STATUS_ACCEPTED) {
                return $existing;
            }
            if (in_array($existing->getStatus(), [
                FiscalDocument::STATUS_SENDING,
                FiscalDocument::STATUS_SENT,
                FiscalDocument::STATUS_RETRYING,
            ], true)) {
                return $existing;
            }
            if ($existing->getStatus() === FiscalDocument::STATUS_QUEUED && $automatic !== false) {
                $this->requeueEmit($existing, $ruc);
                return $existing;
            }
        }

        if (!$this->queue->tryClaimEmit($fingerprint, 120)) {
            $locked = $this->repo->findOneBy(['fiscalFingerprint' => $fingerprint]);
            if ($locked !== null) {
                if ($locked->getStatus() === FiscalDocument::STATUS_QUEUED && $automatic !== false) {
                    $this->requeueEmit($locked, $ruc);
                }
                return $locked;
            }
        }

        $snapshotJson = json_encode($snapshot, JSON_UNESCAPED_UNICODE);
        if ($snapshotJson === false) {
            throw new \InvalidArgumentException('document JSON inválido');
        }

        $customerEmail = $this->extractCustomerEmail($snapshot, $payload);

        $doc = $existing ?? new FiscalDocument();
        if ($existing === null) {
            $doc->setDocumentUuid($this->newUuid());
            $doc->setTenantId($tenantId);
            $doc->setTenantSlug($tenantSlug);
            $doc->setSaleId($saleId);
            $doc->setFiscalFingerprint($fingerprint);
            $this->em->persist($doc);
        } elseif ($existing->getStatus() === FiscalDocument::STATUS_ACCEPTED) {
            return $existing;
        }

        if ($existing === null || $existing->getStatus() !== FiscalDocument::STATUS_ACCEPTED) {
            $doc->setDocumentType($docType);
            $doc->setSeries($series);
            $doc->setNumber($number);
            $doc->setSnapshotJson($snapshotJson);
            $doc->setSnapshotVersion(self::SNAPSHOT_VERSION);
            $doc->setSchemaVersion(self::SCHEMA_VERSION);
            $doc->setGreenterVersion(self::GREENTER_VERSION);
            $doc->setCustomerEmail($customerEmail);
            $doc->setStatus(FiscalDocument::STATUS_QUEUED);
            $doc->setQueuedAt(new \DateTimeImmutable());
        }

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            $dup = $this->repo->findOneBy(['fiscalFingerprint' => $fingerprint]);
            if ($dup !== null) {
                if ($dup->getStatus() === FiscalDocument::STATUS_QUEUED && $automatic !== false) {
                    $this->requeueEmit($dup, $ruc);
                }
                return $dup;
            }
            throw $e;
        }

        if ($automatic !== false) {
            $this->pushEmitJob($doc, $fingerprint, $ruc);
            try {
                if ($this->audit !== null) {
                    $this->audit->fromDocument($doc, 'fiscal_document_queued', FiscalAuditLog::STATUS_QUEUED, [
                        'ruc' => $ruc,
                        'queue_job_id' => $doc->getDocumentUuid(),
                    ]);
                }
            } catch (\Throwable) {
            }
        } else {
            $doc->setStatus(FiscalDocument::STATUS_PENDING);
            $this->em->flush();
        }

        return $doc;
    }

    /**
     * Reencola emisión para un documento ya persistido en status queued (recuperación de huérfanos).
     */
    public function requeueEmit(FiscalDocument $doc, string $ruc): void
    {
        if ($doc->getStatus() !== FiscalDocument::STATUS_QUEUED) {
            throw new \InvalidArgumentException('Solo se puede reencolar un documento en status queued');
        }
        $fingerprint = $doc->getFiscalFingerprint();
        if ($fingerprint === null || $fingerprint === '') {
            throw new \InvalidArgumentException('documento sin fiscal_fingerprint');
        }
        $this->pushEmitJob($doc, $fingerprint, trim($ruc));
        try {
            if ($this->audit !== null) {
                $this->audit->fromDocument($doc, 'fiscal_document_requeued', FiscalAuditLog::STATUS_QUEUED, [
                    'ruc' => trim($ruc),
                    'queue_job_id' => $doc->getDocumentUuid(),
                ]);
            }
        } catch (\Throwable) {
        }
    }

    public static function resolveRucFromSnapshot(string $snapshotJson): ?string
    {
        $snapshot = json_decode($snapshotJson, true);
        if (!is_array($snapshot)) {
            return null;
        }
        $ruc = $snapshot['company_ruc'] ?? ($snapshot['company']['ruc'] ?? null);

        return is_string($ruc) && trim($ruc) !== '' ? trim($ruc) : null;
    }

    private function pushEmitJob(FiscalDocument $doc, string $fingerprint, string $ruc): void
    {
        $this->queue->push(FiscalQueueService::QUEUE_EMIT, [
            'document_uuid' => $doc->getDocumentUuid(),
            'fingerprint' => $fingerprint,
            'ruc' => trim($ruc),
        ]);
        $this->logger->info('fiscal_emit_job_pushed', [
            'document_uuid' => $doc->getDocumentUuid(),
            'fingerprint' => $fingerprint,
            'status' => $doc->getStatus(),
        ]);
    }

    /**
     * @param array<string, mixed> $snapshot
     * @param array<string, mixed> $payload
     */
    private function extractCustomerEmail(array $snapshot, array $payload): ?string
    {
        return FiscalCustomerEmailNormalizer::extractFromPayload($payload, $snapshot);
    }

    private function newUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
