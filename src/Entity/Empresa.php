<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EmpresaRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Empresa: datos por RUC (multitenant). Solo credenciales SOL, certificado, logo y ambiente.
 * Las URLs de SUNAT (FE, RE, GUIA) se toman del .env según ambiente (pruebas/produccion).
 */
#[ORM\Entity(repositoryClass: EmpresaRepository::class)]
#[ORM\Table(name: 'empresa')]
class Empresa
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 11)]
    private string $ruc;

    #[ORM\Column(type: 'string', length: 100)]
    private string $solUser;

    /** Cifrada en reposo (FISCAL_SECRETS_KEY). */
    #[ORM\Column(type: 'encrypted_string', length: 255)]
    private string $solPass;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $certificate = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $logo = null;

    /** Ambiente: pruebas | produccion. Por defecto pruebas. */
    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'pruebas'])]
    private string $ambiente = 'pruebas';

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $tenantId = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $tenantSlug = null;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'sunat_direct'])]
    private string $sendMode = 'sunat_direct';

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $provider = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $pseUser = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $psePass = null;

    /** bearer | basic_auth | custom */
    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'bearer'])]
    private string $connectionType = 'bearer';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $pseBaseUrl = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $pseToken = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $certificatePassword = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $pseSecondaryUser = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $pseMetadataJson = null;

    /** connected | invalid_credentials | certificate_expired | configuration_missing | testing | error */
    #[ORM\Column(type: 'string', length: 30, options: ['default' => 'configuration_missing'])]
    private string $connectionStatus = 'configuration_missing';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $connectionError = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastConnectionCheck = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $automaticSend = true;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $emailEnabled = true;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $retryEnabled = true;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    private ?string $greClientId = null;

    /** Cifrado en reposo. Credencial OAuth de GRE (guías), distinta de la API de validez. */
    #[ORM\Column(type: 'encrypted_string', length: 255, nullable: true)]
    private ?string $greClientSecret = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $greOauthConfiguredAt = null;

    /** API REST «Consulta Integrada de Comprobante de Pago» de SUNAT (validez), credencial del propio tenant. */
    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    private ?string $cpeApiClientId = null;

    /** Cifrado en reposo. Credencial OAuth de la API de validez, distinta de la de GRE. */
    #[ORM\Column(type: 'encrypted_string', length: 255, nullable: true)]
    private ?string $cpeApiClientSecret = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $cpeApiConfiguredAt = null;

    public function getRuc(): string
    {
        return $this->ruc;
    }

    public function setRuc(string $ruc): self
    {
        $this->ruc = $ruc;
        return $this;
    }

    /**
     * Usuario SOL tal como lo espera SUNAT: RUC (11 dígitos) + usuario. SUNAT entrega el usuario
     * secundario solo («BND52148»); si se guardó así, se le antepone el RUC al usarlo (envío
     * directo, consulta de CDR/estado, GRE). Lo guardado no se reescribe aquí. El relleno «PSE» de
     * las empresas PSE se devuelve tal cual: no es un usuario SOL.
     */
    public function getSolUser(): string
    {
        return self::normalizeSolUser($this->solUser, isset($this->ruc) ? $this->ruc : '');
    }

    public static function normalizeSolUser(string $user, string $ruc): string
    {
        $user = trim($user);
        $ruc = trim($ruc);
        if ($user === '' || strtoupper($user) === 'PSE' || preg_match('/^\d{11}$/', $ruc) !== 1) {
            return $user;
        }
        if (preg_match('/^\d{11}/', $user) === 1) {
            return $user;
        }

        return $ruc . $user;
    }

    public function setSolUser(string $solUser): self
    {
        $this->solUser = $solUser;
        return $this;
    }

    public function getSolPass(): string
    {
        return $this->solPass;
    }

    public function setSolPass(string $solPass): self
    {
        $this->solPass = $solPass;
        return $this;
    }

    public function getCertificate(): ?string
    {
        return $this->certificate;
    }

    public function setCertificate(?string $certificate): self
    {
        $this->certificate = $certificate;
        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;
        return $this;
    }

    public function getAmbiente(): string
    {
        return $this->ambiente;
    }

    public function setAmbiente(string $ambiente): self
    {
        $this->ambiente = $ambiente;
        return $this;
    }

    public function getTenantId(): ?int { return $this->tenantId; }
    public function setTenantId(?int $v): self { $this->tenantId = $v; return $this; }
    public function getTenantSlug(): ?string { return $this->tenantSlug; }
    public function setTenantSlug(?string $v): self { $this->tenantSlug = $v; return $this; }
    public function getSendMode(): string { return $this->sendMode; }
    public function setSendMode(string $v): self { $this->sendMode = $v; return $this; }
    public function getProvider(): ?string { return $this->provider; }
    public function setProvider(?string $v): self { $this->provider = $v; return $this; }
    public function getPseUser(): ?string { return $this->pseUser; }
    public function setPseUser(?string $v): self { $this->pseUser = $v; return $this; }
    public function getPsePass(): ?string { return $this->psePass; }
    public function setPsePass(?string $v): self { $this->psePass = $v; return $this; }
    public function getConnectionType(): string { return $this->connectionType; }
    public function setConnectionType(string $v): self { $this->connectionType = $v; return $this; }
    public function getPseBaseUrl(): ?string { return $this->pseBaseUrl; }
    public function setPseBaseUrl(?string $v): self { $this->pseBaseUrl = $v; return $this; }
    public function getPseToken(): ?string { return $this->pseToken; }
    public function setPseToken(?string $v): self { $this->pseToken = $v; return $this; }
    public function getCertificatePassword(): ?string { return $this->certificatePassword; }
    public function setCertificatePassword(?string $v): self { $this->certificatePassword = $v; return $this; }
    public function getPseSecondaryUser(): ?string { return $this->pseSecondaryUser; }
    public function setPseSecondaryUser(?string $v): self { $this->pseSecondaryUser = $v; return $this; }
    public function getPseMetadataJson(): ?string { return $this->pseMetadataJson; }
    public function setPseMetadataJson(?string $v): self { $this->pseMetadataJson = $v; return $this; }
    public function getConnectionStatus(): string { return $this->connectionStatus; }
    public function setConnectionStatus(string $v): self { $this->connectionStatus = $v; return $this; }
    public function getConnectionError(): ?string { return $this->connectionError; }
    public function setConnectionError(?string $v): self { $this->connectionError = $v; return $this; }
    public function getLastConnectionCheck(): ?\DateTimeImmutable { return $this->lastConnectionCheck; }
    public function setLastConnectionCheck(?\DateTimeImmutable $v): self { $this->lastConnectionCheck = $v; return $this; }

    /** Token PSE efectivo (pse_token o pse_pass en columna heredada). */
    public function resolvePseToken(): string
    {
        $token = trim((string) ($this->pseToken ?? ''));
        if ($token !== '') {
            return $token;
        }
        return trim((string) ($this->psePass ?? ''));
    }

  /** URL base PSE del tenant (sin fallback global en runtime). */
    public function resolvePseBaseUrl(): string
    {
        return rtrim(trim((string) ($this->pseBaseUrl ?? '')), '/');
    }

    public function isAutomaticSend(): bool { return $this->automaticSend; }
    public function setAutomaticSend(bool $v): self { $this->automaticSend = $v; return $this; }
    public function isEmailEnabled(): bool { return $this->emailEnabled; }
    public function setEmailEnabled(bool $v): self { $this->emailEnabled = $v; return $this; }
    public function isRetryEnabled(): bool { return $this->retryEnabled; }
    public function setRetryEnabled(bool $v): self { $this->retryEnabled = $v; return $this; }
    public function isEnabled(): bool { return $this->enabled; }
    public function setEnabled(bool $v): self { $this->enabled = $v; return $this; }

    public function getGreClientId(): ?string
    {
        return $this->greClientId;
    }

    public function setGreClientId(?string $v): self
    {
        $this->greClientId = $v !== null && trim($v) !== '' ? trim($v) : null;
        return $this;
    }

    public function getGreClientSecret(): ?string
    {
        return $this->greClientSecret;
    }

    public function setGreClientSecret(?string $v): self
    {
        $this->greClientSecret = $v !== null && $v !== '' ? $v : null;
        return $this;
    }

    public function getGreOauthConfiguredAt(): ?\DateTimeImmutable
    {
        return $this->greOauthConfiguredAt;
    }

    public function getCpeApiClientId(): ?string { return $this->cpeApiClientId; }
    public function setCpeApiClientId(?string $v): self { $this->cpeApiClientId = $v !== null && trim($v) !== '' ? trim($v) : null; return $this; }
    public function getCpeApiClientSecret(): ?string { return $this->cpeApiClientSecret; }
    public function setCpeApiClientSecret(?string $v): self { $this->cpeApiClientSecret = $v !== null && $v !== '' ? $v : null; return $this; }
    public function getCpeApiConfiguredAt(): ?\DateTimeImmutable { return $this->cpeApiConfiguredAt; }
    public function setCpeApiConfiguredAt(?\DateTimeImmutable $v): self { $this->cpeApiConfiguredAt = $v; return $this; }

    public function isCpeApiConfigured(): bool
    {
        return trim((string) ($this->cpeApiClientId ?? '')) !== '' && (string) ($this->cpeApiClientSecret ?? '') !== '';
    }

    public function setGreOauthConfiguredAt(?\DateTimeImmutable $v): self
    {
        $this->greOauthConfiguredAt = $v;
        return $this;
    }
}
