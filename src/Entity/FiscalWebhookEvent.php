<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FiscalWebhookEventRepository")
 * @ORM\Table(name="fiscal_webhook_events")
 */
class FiscalWebhookEvent
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /** @ORM\Column(type="string", length=128, unique=true) */
    private string $eventKey;

    /** @ORM\Column(type="string", length=36) */
    private string $documentUuid;

    /** @ORM\Column(type="string", length=64) */
    private string $payloadHash;

    /** @ORM\Column(type="integer", nullable=true) */
    private ?int $httpStatus = null;

    /** @ORM\Column(type="text", nullable=true) */
    private ?string $responseBody = null;

    /** @ORM\Column(type="datetime", nullable=true) */
    private ?\DateTimeInterface $deliveredAt = null;

    /** @ORM\Column(type="datetime") */
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getEventKey(): string { return $this->eventKey; }
    public function setEventKey(string $v): self { $this->eventKey = $v; return $this; }
    public function getDocumentUuid(): string { return $this->documentUuid; }
    public function setDocumentUuid(string $v): self { $this->documentUuid = $v; return $this; }
    public function getPayloadHash(): string { return $this->payloadHash; }
    public function setPayloadHash(string $v): self { $this->payloadHash = $v; return $this; }
    public function getHttpStatus(): ?int { return $this->httpStatus; }
    public function setHttpStatus(?int $v): self { $this->httpStatus = $v; return $this; }
    public function getResponseBody(): ?string { return $this->responseBody; }
    public function setResponseBody(?string $v): self { $this->responseBody = $v; return $this; }
    public function getDeliveredAt(): ?\DateTimeInterface { return $this->deliveredAt; }
    public function setDeliveredAt(?\DateTimeInterface $v): self { $this->deliveredAt = $v; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
}
