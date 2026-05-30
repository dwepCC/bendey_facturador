<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FiscalDocumentRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Documento fiscal con snapshot JSON inmutable (source of truth fiscal).
 */
#[ORM\Entity(repositoryClass: FiscalDocumentRepository::class)]
#[ORM\Table(name: 'fiscal_documents')]
#[ORM\HasLifecycleCallbacks]
class FiscalDocument
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_OBSERVED = 'observed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ERROR = 'error';
    public const STATUS_RETRYING = 'retrying';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $documentUuid;

    #[ORM\Column(type: 'integer')]
    private int $tenantId;

    #[ORM\Column(type: 'string', length: 100)]
    private string $tenantSlug;

    #[ORM\Column(type: 'integer')]
    private int $saleId;

    #[ORM\Column(type: 'string', length: 10)]
    private string $documentType;

    #[ORM\Column(type: 'string', length: 10)]
    private string $series;

    #[ORM\Column(type: 'string', length: 20)]
    private string $number;

    #[ORM\Column(type: 'string', length: 30, options: ['default' => 'pending'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $sendMode = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $sunatMode = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $customerEmail = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $queuedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $sentAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $acceptedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $rejectedAt = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $xmlUrl = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $xmlSignedUrl = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $cdrUrl = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $pdfUrl = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $hash = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $ticket = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $sunatCode = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $sunatMessage = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $retryCount = 0;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $nextRetryAt = null;

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private ?string $emailStatus = null;

    #[ORM\Column(type: 'text')]
    private string $snapshotJson;

    #[ORM\Column(type: 'string', length: 128, unique: true, nullable: true)]
    private ?string $fiscalFingerprint = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $provider = null;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $snapshotVersion = 1;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => '1.0'])]
    private string $schemaVersion = '1.0';

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $greenterVersion = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $providerVersion = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $pseResponseJson = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $unsignedXmlUrl = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getDocumentUuid(): string { return $this->documentUuid; }
    public function setDocumentUuid(string $v): self { $this->documentUuid = $v; return $this; }
    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $v): self { $this->tenantId = $v; return $this; }
    public function getTenantSlug(): string { return $this->tenantSlug; }
    public function setTenantSlug(string $v): self { $this->tenantSlug = $v; return $this; }
    public function getSaleId(): int { return $this->saleId; }
    public function setSaleId(int $v): self { $this->saleId = $v; return $this; }
    public function getDocumentType(): string { return $this->documentType; }
    public function setDocumentType(string $v): self { $this->documentType = $v; return $this; }
    public function getSeries(): string { return $this->series; }
    public function setSeries(string $v): self { $this->series = $v; return $this; }
    public function getNumber(): string { return $this->number; }
    public function setNumber(string $v): self { $this->number = $v; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): self { $this->status = $v; return $this; }
    public function getSendMode(): ?string { return $this->sendMode; }
    public function setSendMode(?string $v): self { $this->sendMode = $v; return $this; }
    public function getSunatMode(): ?string { return $this->sunatMode; }
    public function setSunatMode(?string $v): self { $this->sunatMode = $v; return $this; }
    public function getCustomerEmail(): ?string { return $this->customerEmail; }
    public function setCustomerEmail(?string $v): self { $this->customerEmail = $v; return $this; }
    public function getQueuedAt(): ?\DateTimeInterface { return $this->queuedAt; }
    public function setQueuedAt(?\DateTimeInterface $v): self { $this->queuedAt = $v; return $this; }
    public function getSentAt(): ?\DateTimeInterface { return $this->sentAt; }
    public function setSentAt(?\DateTimeInterface $v): self { $this->sentAt = $v; return $this; }
    public function getAcceptedAt(): ?\DateTimeInterface { return $this->acceptedAt; }
    public function setAcceptedAt(?\DateTimeInterface $v): self { $this->acceptedAt = $v; return $this; }
    public function getRejectedAt(): ?\DateTimeInterface { return $this->rejectedAt; }
    public function setRejectedAt(?\DateTimeInterface $v): self { $this->rejectedAt = $v; return $this; }
    public function getXmlUrl(): ?string { return $this->xmlUrl; }
    public function setXmlUrl(?string $v): self { $this->xmlUrl = $v; return $this; }
    public function getXmlSignedUrl(): ?string { return $this->xmlSignedUrl; }
    public function setXmlSignedUrl(?string $v): self { $this->xmlSignedUrl = $v; return $this; }
    public function getCdrUrl(): ?string { return $this->cdrUrl; }
    public function setCdrUrl(?string $v): self { $this->cdrUrl = $v; return $this; }
    public function getPdfUrl(): ?string { return $this->pdfUrl; }
    public function setPdfUrl(?string $v): self { $this->pdfUrl = $v; return $this; }
    public function getHash(): ?string { return $this->hash; }
    public function setHash(?string $v): self { $this->hash = $v; return $this; }
    public function getTicket(): ?string { return $this->ticket; }
    public function setTicket(?string $v): self { $this->ticket = $v; return $this; }
    public function getSunatCode(): ?string { return $this->sunatCode; }
    public function setSunatCode(?string $v): self { $this->sunatCode = $v; return $this; }
    public function getSunatMessage(): ?string { return $this->sunatMessage; }
    public function setSunatMessage(?string $v): self { $this->sunatMessage = $v; return $this; }
    public function getRetryCount(): int { return $this->retryCount; }
    public function setRetryCount(int $v): self { $this->retryCount = $v; return $this; }
    public function getNextRetryAt(): ?\DateTimeInterface { return $this->nextRetryAt; }
    public function setNextRetryAt(?\DateTimeInterface $v): self { $this->nextRetryAt = $v; return $this; }
    public function getEmailStatus(): ?string { return $this->emailStatus; }
    public function setEmailStatus(?string $v): self { $this->emailStatus = $v; return $this; }
    public function getSnapshotJson(): string { return $this->snapshotJson; }
    public function setSnapshotJson(string $v): self { $this->snapshotJson = $v; return $this; }
    public function getFiscalFingerprint(): ?string { return $this->fiscalFingerprint; }
    public function setFiscalFingerprint(?string $v): self { $this->fiscalFingerprint = $v; return $this; }
    public function getProvider(): ?string { return $this->provider; }
    public function setProvider(?string $v): self { $this->provider = $v; return $this; }
    public function getSnapshotVersion(): int { return $this->snapshotVersion; }
    public function setSnapshotVersion(int $v): self { $this->snapshotVersion = $v; return $this; }
    public function getSchemaVersion(): string { return $this->schemaVersion; }
    public function setSchemaVersion(string $v): self { $this->schemaVersion = $v; return $this; }
    public function getGreenterVersion(): ?string { return $this->greenterVersion; }
    public function setGreenterVersion(?string $v): self { $this->greenterVersion = $v; return $this; }
    public function getProviderVersion(): ?string { return $this->providerVersion; }
    public function setProviderVersion(?string $v): self { $this->providerVersion = $v; return $this; }
    public function getPseResponseJson(): ?string { return $this->pseResponseJson; }
    public function setPseResponseJson(?string $v): self { $this->pseResponseJson = $v; return $this; }
    public function getUnsignedXmlUrl(): ?string { return $this->unsignedXmlUrl; }
    public function setUnsignedXmlUrl(?string $v): self { $this->unsignedXmlUrl = $v; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }
}
