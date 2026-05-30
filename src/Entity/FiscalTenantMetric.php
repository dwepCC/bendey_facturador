<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FiscalTenantMetricRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FiscalTenantMetricRepository::class)]
#[ORM\Table(name: 'fiscal_tenant_metrics')]
class FiscalTenantMetric
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $tenantId;

    #[ORM\Column(type: 'string', length: 100)]
    private string $tenantSlug;

    #[ORM\Column(type: 'string', length: 11, nullable: true)]
    private ?string $ruc = null;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $periodDate;

    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'day'])]
    private string $periodType = 'day';

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $documentsEmitted = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $documentsAccepted = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $errors = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $retries = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $avgDurationMs = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $successRate = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $provider = null;

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private ?string $sendMode = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $v): self { $this->tenantId = $v; return $this; }
    public function getTenantSlug(): string { return $this->tenantSlug; }
    public function setTenantSlug(string $v): self { $this->tenantSlug = $v; return $this; }
    public function getRuc(): ?string { return $this->ruc; }
    public function setRuc(?string $v): self { $this->ruc = $v; return $this; }
    public function getPeriodDate(): \DateTimeImmutable { return $this->periodDate; }
    public function setPeriodDate(\DateTimeImmutable $v): self { $this->periodDate = $v; return $this; }
    public function getPeriodType(): string { return $this->periodType; }
    public function setPeriodType(string $v): self { $this->periodType = $v; return $this; }
    public function getDocumentsEmitted(): int { return $this->documentsEmitted; }
    public function setDocumentsEmitted(int $v): self { $this->documentsEmitted = $v; return $this; }
    public function getDocumentsAccepted(): int { return $this->documentsAccepted; }
    public function setDocumentsAccepted(int $v): self { $this->documentsAccepted = $v; return $this; }
    public function getErrors(): int { return $this->errors; }
    public function setErrors(int $v): self { $this->errors = $v; return $this; }
    public function getRetries(): int { return $this->retries; }
    public function setRetries(int $v): self { $this->retries = $v; return $this; }
    public function getAvgDurationMs(): ?int { return $this->avgDurationMs; }
    public function setAvgDurationMs(?int $v): self { $this->avgDurationMs = $v; return $this; }
    public function getSuccessRate(): ?string { return $this->successRate; }
    public function setSuccessRate(?string $v): self { $this->successRate = $v; return $this; }
    public function getProvider(): ?string { return $this->provider; }
    public function setProvider(?string $v): self { $this->provider = $v; return $this; }
    public function getSendMode(): ?string { return $this->sendMode; }
    public function setSendMode(?string $v): self { $this->sendMode = $v; return $this; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function touchUpdated(): self { $this->updatedAt = new \DateTimeImmutable(); return $this; }
}
