<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FiscalAuditLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FiscalAuditLogRepository::class)]
#[ORM\Table(name: 'fiscal_audit_logs')]
class FiscalAuditLog
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RETRYING = 'retrying';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $tenantId = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $tenantSlug = null;

    #[ORM\Column(type: 'string', length: 11, nullable: true)]
    private ?string $ruc = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $companyId = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $documentUuid = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $documentType = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $series = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $number = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $saleId = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $externalId = null;

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private ?string $sendMode = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $provider = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $connectionType = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $eventType;

    #[ORM\Column(type: 'string', length: 30)]
    private string $status;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $attempt = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $requestId = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $queueJobId = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $errorCode = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorStack = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $metadataJson = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $durationMs = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): ?int { return $this->tenantId; }
    public function setTenantId(?int $v): self { $this->tenantId = $v; return $this; }
    public function getTenantSlug(): ?string { return $this->tenantSlug; }
    public function setTenantSlug(?string $v): self { $this->tenantSlug = $v; return $this; }
    public function getRuc(): ?string { return $this->ruc; }
    public function setRuc(?string $v): self { $this->ruc = $v; return $this; }
    public function getCompanyId(): ?int { return $this->companyId; }
    public function setCompanyId(?int $v): self { $this->companyId = $v; return $this; }
    public function getDocumentUuid(): ?string { return $this->documentUuid; }
    public function setDocumentUuid(?string $v): self { $this->documentUuid = $v; return $this; }
    public function getDocumentType(): ?string { return $this->documentType; }
    public function setDocumentType(?string $v): self { $this->documentType = $v; return $this; }
    public function getSeries(): ?string { return $this->series; }
    public function setSeries(?string $v): self { $this->series = $v; return $this; }
    public function getNumber(): ?string { return $this->number; }
    public function setNumber(?string $v): self { $this->number = $v; return $this; }
    public function getSaleId(): ?int { return $this->saleId; }
    public function setSaleId(?int $v): self { $this->saleId = $v; return $this; }
    public function getExternalId(): ?string { return $this->externalId; }
    public function setExternalId(?string $v): self { $this->externalId = $v; return $this; }
    public function getSendMode(): ?string { return $this->sendMode; }
    public function setSendMode(?string $v): self { $this->sendMode = $v; return $this; }
    public function getProvider(): ?string { return $this->provider; }
    public function setProvider(?string $v): self { $this->provider = $v; return $this; }
    public function getConnectionType(): ?string { return $this->connectionType; }
    public function setConnectionType(?string $v): self { $this->connectionType = $v; return $this; }
    public function getEventType(): string { return $this->eventType; }
    public function setEventType(string $v): self { $this->eventType = $v; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): self { $this->status = $v; return $this; }
    public function getAttempt(): ?int { return $this->attempt; }
    public function setAttempt(?int $v): self { $this->attempt = $v; return $this; }
    public function getRequestId(): ?string { return $this->requestId; }
    public function setRequestId(?string $v): self { $this->requestId = $v; return $this; }
    public function getQueueJobId(): ?string { return $this->queueJobId; }
    public function setQueueJobId(?string $v): self { $this->queueJobId = $v; return $this; }
    public function getErrorCode(): ?string { return $this->errorCode; }
    public function setErrorCode(?string $v): self { $this->errorCode = $v; return $this; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }
    public function setErrorMessage(?string $v): self { $this->errorMessage = $v; return $this; }
    public function getErrorStack(): ?string { return $this->errorStack; }
    public function setErrorStack(?string $v): self { $this->errorStack = $v; return $this; }
    public function getMetadataJson(): ?string { return $this->metadataJson; }
    public function setMetadataJson(?string $v): self { $this->metadataJson = $v; return $this; }
    public function getStartedAt(): ?\DateTimeImmutable { return $this->startedAt; }
    public function setStartedAt(?\DateTimeImmutable $v): self { $this->startedAt = $v; return $this; }
    public function getFinishedAt(): ?\DateTimeImmutable { return $this->finishedAt; }
    public function setFinishedAt(?\DateTimeImmutable $v): self { $this->finishedAt = $v; return $this; }
    public function getDurationMs(): ?int { return $this->durationMs; }
    public function setDurationMs(?int $v): self { $this->durationMs = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
