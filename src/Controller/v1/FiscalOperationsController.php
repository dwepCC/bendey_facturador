<?php

declare(strict_types=1);

namespace App\Controller\v1;

use App\Service\Fiscal\Observability\FiscalAlertService;
use App\Service\Fiscal\Observability\FiscalAuditService;
use App\Service\Fiscal\Observability\FiscalHealthService;
use App\Service\Fiscal\Observability\FiscalOperationsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * V2.1 — Operaciones y observabilidad fiscal (sin tocar emisión).
 */
#[Route('/api/v1/fiscal')]
class FiscalOperationsController extends AbstractController
{
    private FiscalHealthService $health;
    private FiscalOperationsService $operations;
    private FiscalAlertService $alerts;
    private FiscalAuditService $audit;

    public function __construct(
        FiscalHealthService $health,
        FiscalOperationsService $operations,
        FiscalAlertService $alerts,
        FiscalAuditService $audit
    ) {
        $this->health = $health;
        $this->operations = $operations;
        $this->alerts = $alerts;
        $this->audit = $audit;
    }

    #[Route('/health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse($this->health->check());
    }

    #[Route('/operations/summary', methods: ['GET'])]
    public function summary(): JsonResponse
    {
        return new JsonResponse($this->operations->summary());
    }

    #[Route('/operations/tenants', methods: ['GET'])]
    public function tenants(Request $request): JsonResponse
    {
        return new JsonResponse($this->operations->tenantsTable([
            'tenant_slug' => $this->q($request, 'tenant_slug'),
            'provider' => $this->q($request, 'provider'),
            'send_mode' => $this->q($request, 'send_mode'),
            'connection_status' => $this->q($request, 'connection_status'),
            'errors_only' => filter_var($request->query->get('errors_only', false), FILTER_VALIDATE_BOOLEAN),
            'pending_only' => filter_var($request->query->get('pending_only', false), FILTER_VALIDATE_BOOLEAN),
            'q' => $this->q($request, 'q'),
        ]));
    }

    #[Route('/operations/queue', methods: ['GET'])]
    public function queue(): JsonResponse
    {
        return new JsonResponse($this->operations->queueMonitor());
    }

    #[Route('/alerts', methods: ['GET'])]
    public function alertsList(): JsonResponse
    {
        return new JsonResponse([
            'open_count' => $this->alerts->countOpen(),
            'items' => $this->alerts->listOpen(100),
        ]);
    }

    #[Route('/documents/{uuid}/audit-timeline', methods: ['GET'])]
    public function auditTimeline(string $uuid): JsonResponse
    {
        try {
            return new JsonResponse($this->operations->auditTimeline($uuid));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/documents/{uuid}/cancel', methods: ['POST'])]
    public function cancel(string $uuid): JsonResponse
    {
        try {
            $this->operations->cancelDocument($uuid, $this->audit);
            return new JsonResponse(['status' => 'cancelled'], Response::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    private function q(Request $request, string $key): ?string
    {
        $v = $request->query->get($key);
        if ($v === null || $v === '') {
            return null;
        }
        return (string) $v;
    }
}
