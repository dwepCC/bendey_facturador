<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OutboundEmailLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OutboundEmailLogRepository::class)]
#[ORM\Table(name: 'outbound_email_logs')]
#[ORM\HasLifecycleCallbacks]
class OutboundEmailLog
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 36)]
    private string $documentUuid;

    #[ORM\Column(type: 'string', length: 255)]
    private string $recipientEmail;

    #[ORM\Column(type: 'string', length: 30)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $attempts = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $providerResponse = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $sentAt = null;

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
    public function getRecipientEmail(): string { return $this->recipientEmail; }
    public function setRecipientEmail(string $v): self { $this->recipientEmail = $v; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): self { $this->status = $v; return $this; }
    public function getAttempts(): int { return $this->attempts; }
    public function setAttempts(int $v): self { $this->attempts = $v; return $this; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }
    public function setErrorMessage(?string $v): self { $this->errorMessage = $v; return $this; }
    public function getProviderResponse(): ?string { return $this->providerResponse; }
    public function setProviderResponse(?string $v): self { $this->providerResponse = $v; return $this; }
    public function getSentAt(): ?\DateTimeInterface { return $this->sentAt; }
    public function setSentAt(?\DateTimeInterface $v): self { $this->sentAt = $v; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }
}
