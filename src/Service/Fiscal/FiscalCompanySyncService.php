<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use App\Entity\Empresa;
use App\Repository\EmpresaRepository;
use App\Service\EmpresasService;
use App\Service\Fiscal\Observability\FiscalAuditService;
use App\Service\Fiscal\Provider\PseProviderRegistry;
use App\Service\Gre\GreOAuthCredentialResolver;
use App\Service\ConfigProviderInterface;
use App\Entity\FiscalAuditLog;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Sincronización fiscal unificada empresa → facturador (SSOT).
 * Validación dependiente de send_mode (SUNAT directa vs PSE).
 */
class FiscalCompanySyncService
{
    private EmpresasService $empresasService;
    private EmpresaRepository $empresaRepository;
    private EntityManagerInterface $em;
    private ?FiscalAuditService $audit;
    private ConfigProviderInterface $config;

    public function __construct(
        EmpresasService $empresasService,
        EmpresaRepository $empresaRepository,
        EntityManagerInterface $em,
        ConfigProviderInterface $config,
        ?FiscalAuditService $audit = null
    ) {
        $this->empresasService = $empresasService;
        $this->empresaRepository = $empresaRepository;
        $this->em = $em;
        $this->config = $config;
        $this->audit = $audit;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function sync(array $payload): array
    {
        $ruc = trim((string) ($payload['ruc'] ?? ''));
        if ($ruc === '') {
            throw new \InvalidArgumentException('ruc es obligatorio');
        }

        $sendMode = $this->normalizeSendMode((string) ($payload['send_mode'] ?? 'sunat_direct'));
        $provider = trim((string) ($payload['provider'] ?? ''));
        if ($provider === '') {
            $provider = $sendMode === 'pse' ? 'validapse' : 'sunat';
        }

        $this->validateByMode($sendMode, $payload);

        $entry = $this->buildEmpresaEntry($payload, $sendMode, $provider);
        $this->empresasService->addOrUpdateEmpresas([$ruc => $entry]);

        $entity = $this->empresaRepository->findByRuc($ruc);
        if ($entity !== null) {
            $entity->setConnectionStatus('connected');
            $entity->setConnectionError(null);
            $entity->setLastConnectionCheck(new \DateTimeImmutable());
            $this->em->flush();
            try {
                if ($this->audit !== null) {
                    $this->audit->fromEmpresa($entity, 'fiscal_configuration_updated', FiscalAuditLog::STATUS_SUCCESS);
                }
            } catch (\Throwable) {
            }
        }

        return $this->buildStatus($entity);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validateByMode(string $sendMode, array $payload): void
    {
        if ($sendMode === 'pse') {
            $pse = is_array($payload['pse'] ?? null) ? $payload['pse'] : $payload;
            $provider = PseProviderRegistry::normalizeProvider(
                trim((string) ($payload['provider'] ?? $payload['fiscal_provider'] ?? $payload['pse_provider'] ?? 'validapse'))
            );
            $baseUrl = trim((string) ($pse['base_url'] ?? $pse['pse_base_url'] ?? $payload['pse_base_url'] ?? ''));
            if ($baseUrl === '') {
                $baseUrl = PseProviderRegistry::baseUrl($provider);
            }
            $user = trim((string) ($pse['user'] ?? $pse['pse_user'] ?? $payload['pse_user'] ?? ''));
            $pass = trim((string) ($pse['password'] ?? $pse['pse_password'] ?? $payload['pse_password'] ?? $payload['pse_pass'] ?? ''));
            $token = trim((string) ($pse['token'] ?? $pse['pse_token'] ?? $payload['pse_token'] ?? ''));
            if ($token === '') {
                $token = $pass;
            }

            if ($baseUrl === '') {
                throw new \InvalidArgumentException('PSE: proveedor no soportado o sin URL base configurada');
            }
            $existing = $this->empresaRepository->findByRuc(trim((string) ($payload['ruc'] ?? '')));
            if ($user === '' && ($existing === null || trim((string) ($existing->getPseUser() ?? '')) === '')) {
                throw new \InvalidArgumentException('PSE requiere usuario (credenciales ValidaPSE)');
            }
            if ($token === '' && ($existing === null || $existing->resolvePseToken() === '')) {
                throw new \InvalidArgumentException('PSE requiere contraseña / token de acceso');
            }
            return;
        }

        $sunat = is_array($payload['sunat'] ?? null) ? $payload['sunat'] : $payload;
        $solUser = trim((string) ($sunat['sol_user'] ?? $sunat['SOL_USER'] ?? $payload['SOL_USER'] ?? ''));
        $solPass = trim((string) ($sunat['sol_password'] ?? $sunat['sol_pass'] ?? $sunat['SOL_PASS'] ?? $payload['SOL_PASS'] ?? ''));
        $cert = trim((string) ($sunat['certificate_base64'] ?? $payload['certificate_base64'] ?? ''));
        $ruc = trim((string) ($payload['ruc'] ?? ''));
        $existing = $ruc !== '' ? $this->empresaRepository->findByRuc($ruc) : null;

        if ($solUser === '' && $existing !== null) {
            $solUser = trim($existing->getSolUser());
        }
        if ($solPass === '' && $existing !== null) {
            $solPass = trim($existing->getSolPass());
        }
        if ($solUser === '' || $solPass === '') {
            throw new \InvalidArgumentException('SUNAT directa requiere sol_user y sol_password');
        }
        $hasCert = $cert !== '' || ($existing !== null && $existing->getCertificate());
        if (!$hasCert) {
            throw new \InvalidArgumentException('SUNAT directa requiere certificado digital');
        }

        $ambiente = strtolower(trim((string) ($payload['ambiente'] ?? $payload['environment'] ?? 'pruebas')));
        if ($ambiente === 'produccion') {
            $gre = is_array($payload['gre'] ?? null) ? $payload['gre'] : $payload;
            $greId = trim((string) ($gre['client_id'] ?? $gre['gre_client_id'] ?? $payload['gre_client_id'] ?? $payload['CLIENT_ID'] ?? ''));
            $greSecret = trim((string) ($gre['client_secret'] ?? $gre['gre_client_secret'] ?? $payload['gre_client_secret'] ?? $payload['CLIENT_SECRET'] ?? ''));
            $existingGre = $existing !== null ? trim((string) ($existing->getGreClientId() ?? '')) : '';
            $existingGreSecret = $existing !== null ? trim((string) ($existing->getGreClientSecret() ?? '')) : '';
            if ($greId === '' && $existingGre === '') {
                throw new \InvalidArgumentException('Producción requiere credenciales OAuth GRE (gre.client_id)');
            }
            if ($greSecret === '' && $existingGreSecret === '') {
                throw new \InvalidArgumentException('Producción requiere credenciales OAuth GRE (gre.client_secret)');
            }
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function buildEmpresaEntry(array $payload, string $sendMode, string $provider): array
    {
        $sunat = is_array($payload['sunat'] ?? null) ? $payload['sunat'] : $payload;
        $pse = is_array($payload['pse'] ?? null) ? $payload['pse'] : $payload;

        $entry = [
            'send_mode' => $sendMode,
            'provider' => $provider,
            'connection_type' => strtolower(trim((string) ($payload['connection_type'] ?? 'bearer'))),
            'ambiente' => $payload['ambiente'] ?? $payload['environment'] ?? 'pruebas',
        ];

        if (!empty($payload['tenant_id'])) {
            $entry['tenant_id'] = (int) $payload['tenant_id'];
        }
        if (!empty($payload['tenant_slug'])) {
            $entry['tenant_slug'] = (string) $payload['tenant_slug'];
        }

        if ($sendMode === 'pse') {
            $providerNorm = PseProviderRegistry::normalizeProvider($provider);
            $baseUrl = trim((string) ($pse['base_url'] ?? $pse['pse_base_url'] ?? $payload['pse_base_url'] ?? ''));
            if ($baseUrl === '') {
                $baseUrl = PseProviderRegistry::baseUrl($providerNorm);
            }
            if ($baseUrl !== '') {
                $entry['pse_base_url'] = $baseUrl;
            }
            $entry['connection_type'] = 'bearer';

            $pseUser = trim((string) ($pse['user'] ?? $pse['pse_user'] ?? $payload['pse_user'] ?? ''));
            if ($pseUser !== '') {
                $entry['pse_user'] = $pseUser;
            }
            $pass = trim((string) ($pse['password'] ?? $pse['pse_password'] ?? $payload['pse_password'] ?? $payload['pse_pass'] ?? ''));
            $token = trim((string) ($pse['token'] ?? $pse['pse_token'] ?? $payload['pse_token'] ?? ''));
            if ($token === '') {
                $token = $pass;
            }
            if ($pass !== '') {
                $entry['pse_pass'] = $pass;
            }
            if ($token !== '') {
                $entry['pse_token'] = $token;
            }
            $secUser = trim((string) ($pse['secondary_user'] ?? $payload['pse_secondary_user'] ?? ''));
            if ($secUser !== '') {
                $entry['pse_secondary_user'] = $secUser;
            }
            $meta = $pse['metadata'] ?? $payload['pse_metadata_json'] ?? null;
            if ($meta !== null && $meta !== '') {
                $entry['pse_metadata_json'] = is_string($meta) ? $meta : json_encode($meta, JSON_UNESCAPED_UNICODE);
            }
        } else {
            $solUser = trim((string) ($sunat['sol_user'] ?? $sunat['SOL_USER'] ?? $payload['SOL_USER'] ?? ''));
            if ($solUser !== '') {
                $entry['SOL_USER'] = $solUser;
            }
            $solPass = trim((string) ($sunat['sol_password'] ?? $sunat['sol_pass'] ?? $sunat['SOL_PASS'] ?? $payload['SOL_PASS'] ?? ''));
            if ($solPass !== '') {
                $entry['SOL_PASS'] = $solPass;
            }
            $certB64 = trim((string) ($sunat['certificate_base64'] ?? $payload['certificate_base64'] ?? ''));
            if ($certB64 !== '') {
                $entry['certificate_base64'] = $certB64;
            }
            $certPass = trim((string) ($sunat['certificate_password'] ?? $payload['certificate_password'] ?? ''));
            if ($certPass !== '') {
                $entry['certificate_password'] = $certPass;
            }
            if (!empty($payload['logo_base64'])) {
                $entry['logo_base64'] = (string) $payload['logo_base64'];
            }
            $gre = is_array($payload['gre'] ?? null) ? $payload['gre'] : $payload;
            $greId = trim((string) ($gre['client_id'] ?? $gre['gre_client_id'] ?? $payload['gre_client_id'] ?? $payload['CLIENT_ID'] ?? ''));
            if ($greId !== '') {
                $entry['gre_client_id'] = $greId;
                $entry['CLIENT_ID'] = $greId;
            }
            $greSecret = trim((string) ($gre['client_secret'] ?? $gre['gre_client_secret'] ?? $payload['gre_client_secret'] ?? $payload['CLIENT_SECRET'] ?? ''));
            if ($greSecret !== '') {
                $entry['gre_client_secret'] = $greSecret;
                $entry['CLIENT_SECRET'] = $greSecret;
                $entry['gre_oauth_configured_at'] = (new \DateTimeImmutable())->format(DATE_ATOM);
            }
        }

        foreach (['automatic_send', 'email_enabled', 'retry_enabled', 'enabled'] as $flag) {
            if (array_key_exists($flag, $payload)) {
                $entry[$flag] = (bool) $payload[$flag];
            }
        }

        return $entry;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildStatus(?Empresa $entity): array
    {
        if ($entity === null) {
            return ['connection_status' => 'configuration_missing'];
        }
        $greConfigured = GreOAuthCredentialResolver::isConfigured($entity, $this->config);

        return [
            'ruc' => $entity->getRuc(),
            'tenant_id' => $entity->getTenantId(),
            'tenant_slug' => $entity->getTenantSlug(),
            'send_mode' => $entity->getSendMode(),
            'provider' => $entity->getProvider(),
            'connection_type' => $entity->getConnectionType(),
            'ambiente' => $entity->getAmbiente(),
            'connection_status' => $entity->getConnectionStatus(),
            'connection_error' => $entity->getConnectionError(),
            'last_connection_check' => $entity->getLastConnectionCheck()
                ? $entity->getLastConnectionCheck()->format(DATE_ATOM) : null,
            'pse_base_url_configured' => $entity->resolvePseBaseUrl() !== '',
            'pse_token_configured' => $entity->resolvePseToken() !== '',
            'sol_configured' => trim($entity->getSolUser()) !== '' && trim($entity->getSolPass()) !== '',
            'certificate_configured' => $entity->getCertificate() !== null && trim((string) $entity->getCertificate()) !== '',
            'gre_oauth_configured' => $greConfigured,
            'gre_oauth_configured_at' => $entity->getGreOauthConfiguredAt()
                ? $entity->getGreOauthConfiguredAt()->format(DATE_ATOM) : null,
            'gre_oauth_source' => trim((string) ($entity->getGreClientId() ?? '')) !== '' ? 'empresa' : (
                strtolower(trim($entity->getAmbiente())) === 'produccion' ? null : 'nubefact_test_fallback'
            ),
            'enabled' => $entity->isEnabled(),
        ];
    }

    private function normalizeSendMode(string $mode): string
    {
        $m = strtolower(trim($mode));
        if ($m === 'sunat' || $m === '') {
            return 'sunat_direct';
        }
        if ($m === 'pse') {
            return 'pse';
        }
        return $m;
    }
}
