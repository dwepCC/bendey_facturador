<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FiscalEmitAttemptRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FiscalEmitAttemptRepository::class)]
#[ORM\Table(name: 'fiscal_emit_attempts')]
class FiscalEmitAttempt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 36)]
    private string $documentUuid;

    #[ORM\Column(type: 'integer')]
    private int $attemptNumber;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $provider = null;

    #[ORM\Column(type: 'string', length: 30)]
    private string $status;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $sunatCode = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $sunatMessage = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $pseMessage = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $durationMs = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getDocumentUuid(): string { return $this->documentUuid; }
    public function setDocumentUuid(string $v): self { $this->documentUuid = $v; return $this; }
    public function getAttemptNumber(): int { return $this->attemptNumber; }
    public function setAttemptNumber(int $v): self { $this->attemptNumber = $v; return $this; }
    public function getProvider(): ?string { return $this->provider; }
    public function setProvider(?string $v): self { $this->provider = $v; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): self { $this->status = $v; return $this; }
    public function getSunatCode(): ?string { return $this->sunatCode; }
    public function setSunatCode(?string $v): self { $this->sunatCode = $v; return $this; }
    public function getSunatMessage(): ?string { return $this->sunatMessage; }
    public function setSunatMessage(?string $v): self { $this->sunatMessage = $v; return $this; }
    public function getPseMessage(): ?string { return $this->pseMessage; }
    public function setPseMessage(?string $v): self { $this->pseMessage = $v; return $this; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }
    public function setErrorMessage(?string $v): self { $this->errorMessage = $v; return $this; }
    public function getDurationMs(): ?int { return $this->durationMs; }
    public function setDurationMs(?int $v): self { $this->durationMs = $v; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
}
