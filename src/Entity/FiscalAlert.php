<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FiscalAlertRepository")
 * @ORM\Table(name="fiscal_alerts")
 */
class FiscalAlert
{
    public const TYPE_CERT_EXPIRING = 'cert_expiring';
    public const TYPE_TENANT_DISCONNECTED = 'tenant_disconnected';
    public const TYPE_CONSECUTIVE_ERRORS = 'consecutive_errors';
    public const TYPE_QUEUE_SATURATED = 'queue_saturated';
    public const TYPE_RETRY_ANOMALY = 'retry_anomaly';

    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private ?int $id = null;

    /** @ORM\Column(type="integer", nullable=true) */
    private ?int $tenantId = null;

    /** @ORM\Column(type="string", length=100, nullable=true) */
    private ?string $tenantSlug = null;

    /** @ORM\Column(type="string", length=11, nullable=true) */
    private ?string $ruc = null;

    /** @ORM\Column(type="string", length=50) */
    private string $alertType;

    /** @ORM\Column(type="string", length=20, options={"default":"warning"}) */
    private string $severity = 'warning';

    /** @ORM\Column(type="string", length=500) */
    private string $message;

    /** @ORM\Column(type="text", nullable=true) */
    private ?string $metadataJson = null;

    /** @ORM\Column(type="datetime_immutable", nullable=true) */
    private ?\DateTimeImmutable $acknowledgedAt = null;

    /** @ORM\Column(type="datetime_immutable", nullable=true) */
    private ?\DateTimeImmutable $resolvedAt = null;

    /** @ORM\Column(type="datetime_immutable") */
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
    public function getAlertType(): string { return $this->alertType; }
    public function setAlertType(string $v): self { $this->alertType = $v; return $this; }
    public function getSeverity(): string { return $this->severity; }
    public function setSeverity(string $v): self { $this->severity = $v; return $this; }
    public function getMessage(): string { return $this->message; }
    public function setMessage(string $v): self { $this->message = $v; return $this; }
    public function getMetadataJson(): ?string { return $this->metadataJson; }
    public function setMetadataJson(?string $v): self { $this->metadataJson = $v; return $this; }
    public function getAcknowledgedAt(): ?\DateTimeImmutable { return $this->acknowledgedAt; }
    public function setAcknowledgedAt(?\DateTimeImmutable $v): self { $this->acknowledgedAt = $v; return $this; }
    public function getResolvedAt(): ?\DateTimeImmutable { return $this->resolvedAt; }
    public function setResolvedAt(?\DateTimeImmutable $v): self { $this->resolvedAt = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
