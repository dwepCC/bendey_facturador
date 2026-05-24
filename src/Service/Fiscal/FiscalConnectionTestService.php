<?php

declare(strict_types=1);

namespace App\Service\Fiscal;

use App\Entity\Empresa;
use App\Exception\EmpresaNoRegistradaException;
use App\Repository\EmpresaRepository;
use App\Service\Fiscal\Provider\FiscalConnectionResult;
use App\Service\Fiscal\Provider\FiscalProviderResolver;
use App\Service\Fiscal\Observability\FiscalAuditService;
use App\Entity\FiscalAuditLog;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Prueba conexión fiscal real por empresa (SUNAT directa o PSE).
 */
class FiscalConnectionTestService
{
    private EmpresaRepository $empresaRepository;
    private FiscalProviderResolver $providerResolver;
    private EntityManagerInterface $em;
    private FiscalCompanySyncService $syncService;
    private ?FiscalAuditService $audit;

    public function __construct(
        EmpresaRepository $empresaRepository,
        FiscalProviderResolver $providerResolver,
        EntityManagerInterface $em,
        FiscalCompanySyncService $syncService,
        ?FiscalAuditService $audit = null
    ) {
        $this->empresaRepository = $empresaRepository;
        $this->providerResolver = $providerResolver;
        $this->em = $em;
        $this->syncService = $syncService;
        $this->audit = $audit;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function test(array $payload): array
    {
        $ruc = trim((string) ($payload['ruc'] ?? ''));
        if ($ruc === '') {
            throw new \InvalidArgumentException('ruc es obligatorio');
        }

        $entity = $this->empresaRepository->findByRuc($ruc);
        if ($entity === null) {
            throw new EmpresaNoRegistradaException($ruc);
        }

        $entity->setConnectionStatus('testing');
        $entity->setConnectionError(null);
        $this->em->flush();

        try {
            $result = $this->providerResolver->validateConnection($entity);
        } catch (\Throwable $e) {
            $result = FiscalConnectionResult::fail('error', $e->getMessage());
        }

        $entity->setConnectionStatus($result->status);
        $entity->setConnectionError($result->success ? null : ($result->message ?? 'Error'));
        $entity->setLastConnectionCheck(new \DateTimeImmutable());
        $this->em->flush();

        try {
            if ($this->audit !== null) {
                $this->audit->fromEmpresa($entity, 'fiscal_connection_test', $result->success
                    ? FiscalAuditLog::STATUS_SUCCESS
                    : FiscalAuditLog::STATUS_FAILED, [
                    'error_message' => $result->success ? null : ($result->message ?? 'Error'),
                ]);
            }
        } catch (\Throwable) {
        }

        return array_merge($this->syncService->buildStatus($entity), [
            'success' => $result->success,
            'message' => $result->message,
        ]);
    }
}
