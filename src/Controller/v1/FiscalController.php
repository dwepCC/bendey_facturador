<?php

declare(strict_types=1);

namespace App\Controller\v1;

use App\Entity\FiscalDocument;
use App\Entity\Empresa;
use App\Repository\EmpresaRepository;
use App\Repository\FiscalDocumentRepository;
use App\Service\Fiscal\CdrNormalizer;
use App\Service\Fiscal\FiscalBulkActionService;
use App\Service\Fiscal\FiscalCompanySyncService;
use App\Service\Fiscal\FiscalConnectionTestService;
use App\Service\Fiscal\FiscalDocumentDetailService;
use App\Service\Fiscal\FiscalDocumentService;
use App\Service\Fiscal\FiscalDocumentPdfResolver;
use App\Service\Fiscal\FiscalFileFetcher;
use App\Service\Fiscal\FiscalPdfRenderException;
use App\Service\Fiscal\FiscalQueueService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API fiscal async — source of truth de documentos electrónicos SUNAT.
 *
 * @Route("/api/v1/fiscal")
 */
class FiscalController extends AbstractController
{
    private FiscalDocumentService $documentService;
    private FiscalDocumentRepository $repo;
    private FiscalDocumentDetailService $detailService;
    private FiscalQueueService $queue;
    private FiscalFileFetcher $fileFetcher;
    private FiscalBulkActionService $bulkService;
    private EmpresaRepository $empresaRepo;
    private FiscalCompanySyncService $companySyncService;
    private FiscalConnectionTestService $connectionTestService;
    private FiscalDocumentPdfResolver $pdfResolver;

    public function __construct(
        FiscalDocumentService $documentService,
        FiscalDocumentRepository $repo,
        FiscalDocumentDetailService $detailService,
        FiscalQueueService $queue,
        FiscalFileFetcher $fileFetcher,
        FiscalBulkActionService $bulkService,
        EmpresaRepository $empresaRepo,
        FiscalCompanySyncService $companySyncService,
        FiscalConnectionTestService $connectionTestService,
        FiscalDocumentPdfResolver $pdfResolver
    ) {
        $this->documentService = $documentService;
        $this->repo = $repo;
        $this->detailService = $detailService;
        $this->queue = $queue;
        $this->fileFetcher = $fileFetcher;
        $this->bulkService = $bulkService;
        $this->empresaRepo = $empresaRepo;
        $this->companySyncService = $companySyncService;
        $this->connectionTestService = $connectionTestService;
        $this->pdfResolver = $pdfResolver;
    }

    /**
     * @Route("/emit", methods={"POST"})
     */
    public function emit(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'JSON inválido'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $doc = $this->documentService->enqueue($payload);
            return new JsonResponse([
                'document_uuid' => $doc->getDocumentUuid(),
                'status' => $doc->getStatus(),
                'queued_at' => $doc->getQueuedAt() ? $doc->getQueuedAt()->format(DATE_ATOM) : null,
            ], Response::HTTP_ACCEPTED);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Sincroniza configuración fiscal completa de empresa (SSOT).
     *
     * @Route("/company-sync", methods={"POST"})
     */
    public function companySync(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'JSON inválido'], Response::HTTP_BAD_REQUEST);
        }
        try {
            $status = $this->companySyncService->sync($payload);
            return new JsonResponse(['ok' => true, 'status' => $status], Response::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Prueba conexión fiscal real (SUNAT directa o PSE).
     *
     * @Route("/test-connection", methods={"POST"})
     */
    public function testConnection(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'JSON inválido'], Response::HTTP_BAD_REQUEST);
        }
        try {
            $result = $this->connectionTestService->test($payload);
            return new JsonResponse($result, Response::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Listado paginado de empresas (panel admin).
     *
     * @Route("/companies", methods={"GET"})
     */
    public function companies(Request $request): JsonResponse
    {
        $filters = $this->parseCompanyFilters($request);
        $limit = min(100, max(1, (int) $request->query->get('limit', 25)));
        $offset = max(0, (int) $request->query->get('offset', 0));
        $includeTotal = filter_var($request->query->get('include_total', true), FILTER_VALIDATE_BOOLEAN);

        $rows = $this->empresaRepo->findForAdminList($filters, $limit, $offset);
        $response = [
            'items' => array_map([$this, 'serializeCompanyAdmin'], $rows),
            'offset' => $offset,
            'limit' => $limit,
        ];
        if ($includeTotal) {
            $response['total'] = $this->empresaRepo->countForAdminList($filters);
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/stats", methods={"GET"})
     */
    public function stats(Request $request): JsonResponse
    {
        $tenantSlug = $this->q($request, 'tenant_slug');
        $companyRuc = $this->q($request, 'company_ruc');
        $fromStr = $request->query->get('from');
        $toStr = $request->query->get('to');
        $tenantId = $request->query->get('tenant_id');

        if (($tenantSlug === null || $tenantSlug === '') && $companyRuc !== null && $companyRuc !== '') {
            $ruc = (string) preg_replace('/\D/', '', $companyRuc);
            $empresa = $ruc !== '' ? $this->empresaRepo->findByRuc($ruc) : null;
            if ($empresa !== null && $empresa->getTenantSlug() !== null && $empresa->getTenantSlug() !== '') {
                $tenantSlug = $empresa->getTenantSlug();
            }
        }

        return new JsonResponse($this->detailService->globalStats(
            $tenantSlug,
            $fromStr ? new \DateTimeImmutable((string) $fromStr) : null,
            $toStr ? new \DateTimeImmutable((string) $toStr) : null,
            $tenantId !== null && $tenantId !== '' ? (int) $tenantId : null
        ));
    }

    /**
     * @Route("/documents", methods={"GET"})
     */
    public function list(Request $request): JsonResponse
    {
        $filters = $this->parseFilters($request);
        $limit = (int) $filters['limit'];
        $useCursor = !empty($filters['cursor']);
        $docs = $this->repo->findByFilters($filters);

        $hasMore = false;
        $nextCursor = null;
        if ($useCursor && count($docs) > $limit) {
            $hasMore = true;
            $nextCursor = $this->repo->buildNextCursor($docs, $limit);
            $docs = array_slice($docs, 0, $limit);
        }

        $includeTotal = filter_var($request->query->get('include_total', false), FILTER_VALIDATE_BOOLEAN);
        $total = null;
        if (!$useCursor && $includeTotal) {
            $total = $this->repo->countByFilters($filters);
        }

        $tenantSlugs = array_values(array_unique(array_filter(array_map(static function (FiscalDocument $doc): string {
            return $doc->getTenantSlug();
        }, $docs))));
        $empresaBySlug = $this->empresaRepo->findByTenantSlugs($tenantSlugs);

        $response = [
            'items' => array_map(function (FiscalDocument $doc) use ($empresaBySlug): array {
                $slug = $doc->getTenantSlug();
                return $this->serializeDocSummary($doc, $empresaBySlug[$slug] ?? null);
            }, $docs),
            'counts' => $this->repo->countByStatus(
                $filters['tenant_slug'],
                $filters['from'] ?? null,
                $filters['to'] ?? null
            ),
        ];

        if ($useCursor) {
            $response['next_cursor'] = $nextCursor;
            $response['has_more'] = $hasMore;
            $response['limit'] = $limit;
        } else {
            $response['offset'] = (int) $filters['offset'];
            $response['limit'] = $limit;
            if ($total !== null) {
                $response['total'] = $total;
            }
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/documents/bulk/{action}", methods={"POST"}, requirements={"action": "send|retry|force|email|poll"})
     */
    public function bulk(string $action, Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);
        if (!is_array($body)) {
            return new JsonResponse(['error' => 'JSON inválido'], Response::HTTP_BAD_REQUEST);
        }

        $max = min(500, max(1, (int) ($body['max'] ?? 100)));
        $tenantScope = !empty($body['tenant_slug']) ? (string) $body['tenant_slug'] : null;
        try {
            if (!empty($body['document_uuids']) && is_array($body['document_uuids'])) {
                $result = $this->bulkService->byUuids($body['document_uuids'], $action, $max, $tenantScope);
            } elseif (!empty($body['filters']) && is_array($body['filters'])) {
                $filters = $this->normalizeFilterArray($body['filters']);
                $filters['electronic_only'] = true;
                $result = $this->bulkService->byFilters($filters, $action, $max);
            } else {
                return new JsonResponse(['error' => 'document_uuids o filters requerido'], Response::HTTP_BAD_REQUEST);
            }
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(array_merge(['action' => $action], $result), Response::HTTP_ACCEPTED);
    }

    /**
     * @Route("/documents/{uuid}", methods={"GET"})
     */
    public function detail(string $uuid): JsonResponse
    {
        $doc = $this->detailService->findDocument($uuid);
        if ($doc === null) {
            return new JsonResponse(['error' => 'no encontrado'], Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse($this->detailService->buildDetail($doc));
    }

    /**
     * @Route("/documents/{uuid}/send", methods={"POST"})
     */
    public function sendManual(string $uuid): JsonResponse
    {
        return $this->enqueueAction($uuid, FiscalQueueService::QUEUE_EMIT, 'queued');
    }

    /**
     * @Route("/documents/{uuid}/retry", methods={"POST"})
     */
    public function retry(string $uuid): JsonResponse
    {
        return $this->enqueueAction($uuid, FiscalQueueService::QUEUE_EMIT, 'retry_queued');
    }

    /**
     * @Route("/documents/{uuid}/force", methods={"POST"})
     */
    public function forceSend(string $uuid): JsonResponse
    {
        return $this->enqueueAction($uuid, FiscalQueueService::QUEUE_EMIT, 'force_queued');
    }

    /**
     * @Route("/documents/{uuid}/email", methods={"POST"})
     */
    public function resendEmail(string $uuid): JsonResponse
    {
        return $this->enqueueAction($uuid, FiscalQueueService::QUEUE_EMAIL, 'email_queued');
    }

    /**
     * @Route("/documents/{uuid}/poll", methods={"POST"})
     */
    public function pollTicket(string $uuid): JsonResponse
    {
        return $this->enqueueAction($uuid, FiscalQueueService::QUEUE_STATUS_POLL, 'poll_queued');
    }

    /**
     * @Route("/documents/{uuid}/generate-pdf", methods={"POST"})
     */
    public function generatePdf(string $uuid): JsonResponse
    {
        $doc = $this->repo->findOneBy(['documentUuid' => $uuid]);
        if ($doc === null) {
            return new JsonResponse(['error' => 'no encontrado'], Response::HTTP_NOT_FOUND);
        }
        if (!$this->pdfResolver->canGenerate($doc)) {
            return new JsonResponse([
                'error' => 'No se puede generar PDF: falta XML firmado o el tipo de documento no lo soporta.',
            ], Response::HTTP_BAD_REQUEST);
        }
        try {
            $pdf = $this->pdfResolver->generate($doc, true);
        } catch (FiscalPdfRenderException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        if ($pdf === null || $pdf === '') {
            return new JsonResponse([
                'error' => 'No se pudo generar el PDF. Verifique WKHTMLTOPDF_PATH en .env.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'status' => 'ok',
            'pdf_url' => $doc->getPdfUrl(),
            'download_url' => '/api/v1/fiscal/documents/' . $uuid . '/download/pdf',
        ]);
    }

    /**
     * @Route("/documents/{uuid}/download/{type}", methods={"GET"}, requirements={"type": "xml|signed_xml|cdr|pdf|unsigned_xml"})
     */
    public function download(string $uuid, string $type, Request $request): Response
    {
        $doc = $this->repo->findOneBy(['documentUuid' => $uuid]);
        if ($doc === null) {
            return new Response('Not found', 404);
        }

        if ($type === 'pdf') {
            $pdfBytes = $this->pdfResolver->resolve($doc, true);
            if ($pdfBytes === null || $pdfBytes === '') {
                return new Response(
                    'PDF no disponible. Compruebe WKHTMLTOPDF_PATH y que el documento tenga XML firmado almacenado.',
                    404
                );
            }
            $filename = sprintf('%s-%s-%s.pdf', $doc->getDocumentType(), $doc->getSeries(), $doc->getNumber());
            $inline = $request->query->get('view') === 'inline';
            $response = new Response($pdfBytes);
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set(
                'Content-Disposition',
                ($inline ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT)
                . '; filename="' . $filename . '"'
            );

            return $response;
        }

        $urlMap = [
            'xml' => $doc->getXmlUrl(),
            'signed_xml' => $doc->getXmlSignedUrl(),
            'unsigned_xml' => $doc->getUnsignedXmlUrl(),
            'cdr' => $doc->getCdrUrl(),
        ];
        $url = $urlMap[$type] ?? null;
        $fetched = $this->fileFetcher->fetch($url);
        if ($fetched === null) {
            return new Response('Archivo no disponible', 404);
        }
        if ($type === 'cdr' && CdrNormalizer::isXml($fetched['content'])) {
            $normalized = CdrNormalizer::toSunatZip(
                $fetched['content'],
                CdrNormalizer::filenameBaseFromDocument($doc)
            );
            if ($normalized !== null) {
                $fetched['content'] = $normalized;
                $fetched['mime'] = 'application/zip';
            }
        }
        $ext = $type === 'cdr' ? 'zip' : 'xml';
        $filename = sprintf('%s-%s-%s.%s', $doc->getDocumentType(), $doc->getSeries(), $doc->getNumber(), $ext);
        $response = new Response($fetched['content']);
        $response->headers->set('Content-Type', $fetched['mime']);
        $response->headers->set('Content-Disposition', ResponseHeaderBag::DISPOSITION_ATTACHMENT . '; filename="' . $filename . '"');

        return $response;
    }

    private function enqueueAction(string $uuid, string $queue, string $status): JsonResponse
    {
        $doc = $this->repo->findOneBy(['documentUuid' => $uuid]);
        if ($doc === null) {
            return new JsonResponse(['error' => 'no encontrado'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->queue->push($queue, ['document_uuid' => $uuid]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($this->queue->isEnabled()) {
            return new JsonResponse(['status' => $status], Response::HTTP_ACCEPTED);
        }

        $doc = $this->repo->findOneBy(['documentUuid' => $uuid]);
        return new JsonResponse([
            'status' => $doc !== null ? $doc->getStatus() : $status,
            'sync' => true,
            'sunat_code' => $doc !== null ? $doc->getSunatCode() : null,
            'sunat_message' => $doc !== null ? $doc->getSunatMessage() : null,
        ], Response::HTTP_OK);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseFilters(Request $request): array
    {
        $fromStr = $request->query->get('from');
        $toStr = $request->query->get('to');
        $tenantId = $request->query->get('tenant_id');

        return [
            'tenant_slug' => $this->q($request, 'tenant_slug'),
            'tenant_id' => $tenantId !== null && $tenantId !== '' ? (int) $tenantId : null,
            'sale_id' => $request->query->get('sale_id') !== null && $request->query->get('sale_id') !== ''
                ? (int) $request->query->get('sale_id') : null,
            'status' => $this->q($request, 'status'),
            'document_type' => $this->q($request, 'document_type'),
            'provider' => $this->q($request, 'provider'),
            'send_mode' => $this->q($request, 'send_mode'),
            'series' => $this->q($request, 'series'),
            'number' => $this->q($request, 'number'),
            'customer_email' => $this->q($request, 'customer_email'),
            'customer_name' => $this->q($request, 'customer_name'),
            'company_ruc' => $this->q($request, 'company_ruc'),
            'from' => $fromStr ? new \DateTimeImmutable((string) $fromStr) : null,
            'to' => $toStr ? new \DateTimeImmutable((string) $toStr) : null,
            'limit' => min(200, max(1, (int) $request->query->get('limit', 50))),
            'offset' => max(0, (int) $request->query->get('offset', 0)),
            'cursor' => $this->q($request, 'cursor'),
            'errors_only' => filter_var($request->query->get('errors_only', false), FILTER_VALIDATE_BOOLEAN),
            'pending_only' => filter_var($request->query->get('pending_only', false), FILTER_VALIDATE_BOOLEAN),
            'retry_only' => filter_var($request->query->get('retry_only', false), FILTER_VALIDATE_BOOLEAN),
            'email_pending' => filter_var($request->query->get('email_pending', false), FILTER_VALIDATE_BOOLEAN),
            'email_sent' => $request->query->has('email_sent') ? $request->query->get('email_sent') : null,
            'electronic_only' => !filter_var($request->query->get('include_non_fiscal', false), FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, mixed>
     */
    private function normalizeFilterArray(array $raw): array
    {
        $out = [];
        foreach ([
            'tenant_slug', 'status', 'document_type', 'provider', 'send_mode',
            'series', 'number', 'customer_email', 'customer_name', 'company_ruc',
        ] as $key) {
            if (!empty($raw[$key])) {
                $out[$key] = (string) $raw[$key];
            }
        }
        if (!empty($raw['tenant_id'])) {
            $out['tenant_id'] = (int) $raw['tenant_id'];
        }
        if (!empty($raw['from'])) {
            $out['from'] = new \DateTimeImmutable((string) $raw['from']);
        }
        if (!empty($raw['to'])) {
            $out['to'] = new \DateTimeImmutable((string) $raw['to']);
        }
        foreach (['errors_only', 'pending_only', 'retry_only', 'email_pending'] as $flag) {
            if (!empty($raw[$flag])) {
                $out[$flag] = filter_var($raw[$flag], FILTER_VALIDATE_BOOLEAN);
            }
        }
        if (array_key_exists('email_sent', $raw)) {
            $out['email_sent'] = $raw['email_sent'];
        }

        return $out;
    }

    private function q(Request $request, string $key): ?string
    {
        $v = $request->query->get($key);
        if ($v === null || $v === '') {
            return null;
        }

        return (string) $v;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseCompanyFilters(Request $request): array
    {
        $filters = [
            'ruc' => $this->q($request, 'ruc'),
            'tenant_slug' => $this->q($request, 'tenant_slug'),
            'send_mode' => $this->q($request, 'send_mode'),
            'connection_status' => $this->q($request, 'connection_status'),
            'q' => $this->q($request, 'q'),
            'from' => $this->parseDateFilter($request->query->get('from'), false),
            'to' => $this->parseDateFilter($request->query->get('to'), true),
        ];
        if ($request->query->has('enabled')) {
            $filters['enabled'] = filter_var($request->query->get('enabled'), FILTER_VALIDATE_BOOLEAN);
        }

        return $filters;
    }

    private function parseDateFilter(mixed $value, bool $endOfDay): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }
        $dt = new \DateTimeImmutable((string) $value);
        if ($endOfDay && !str_contains((string) $value, 'T') && !str_contains((string) $value, ' ')) {
            $dt = $dt->setTime(23, 59, 59);
        }

        return $dt;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCompanyAdmin(Empresa $empresa): array
    {
        $sendMode = $empresa->getSendMode();
        $isPse = $sendMode === 'pse';

        return [
            'ruc' => $empresa->getRuc(),
            'tenant_id' => $empresa->getTenantId(),
            'tenant_slug' => $empresa->getTenantSlug(),
            'ambiente' => $empresa->getAmbiente(),
            'send_mode' => $sendMode,
            'send_mode_label' => $isPse ? 'PSE' : 'SUNAT directo',
            'provider' => $empresa->getProvider(),
            'connection_status' => $empresa->getConnectionStatus(),
            'connection_error' => $empresa->getConnectionError(),
            'last_connection_check' => $empresa->getLastConnectionCheck()
                ? $empresa->getLastConnectionCheck()->format(DATE_ATOM) : null,
            'enabled' => $empresa->isEnabled(),
            'sol_user' => $empresa->getSolUser(),
            'has_certificate' => $empresa->getCertificate() !== null && $empresa->getCertificate() !== '',
            'pse_configured' => $isPse && $empresa->resolvePseBaseUrl() !== '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDocSummary(FiscalDocument $doc, ?Empresa $empresa = null): array
    {
        $snapshot = json_decode($doc->getSnapshotJson(), true);
        $customerName = null;
        $companyRuc = null;
        $companyName = null;
        $total = null;
        if (is_array($snapshot)) {
            if (isset($snapshot['customer']) && is_array($snapshot['customer'])) {
                $customerName = $snapshot['customer']['razonSocial']
                    ?? $snapshot['customer']['nombre']
                    ?? $snapshot['customer']['name']
                    ?? null;
            }
            $companyRuc = $snapshot['company_ruc'] ?? ($snapshot['company']['ruc'] ?? null);
            $companyName = $snapshot['company']['razonSocial']
                ?? $snapshot['company']['nombreComercial']
                ?? $snapshot['company']['name']
                ?? null;
            $total = $snapshot['mtoImpVenta'] ?? $snapshot['total'] ?? $snapshot['importeTotal'] ?? null;
        }

        if ($empresa !== null) {
            if ($companyRuc === null) {
                $companyRuc = $empresa->getRuc();
            }
            if ($companyName === null || $companyName === '') {
                $companyName = $doc->getTenantSlug();
            }
        }

        return [
            'document_uuid' => $doc->getDocumentUuid(),
            'tenant_id' => $doc->getTenantId(),
            'tenant_slug' => $doc->getTenantSlug(),
            'sale_id' => $doc->getSaleId(),
            'document_type' => $doc->getDocumentType(),
            'series' => $doc->getSeries(),
            'number' => $doc->getNumber(),
            'status' => $doc->getStatus(),
            'send_mode' => $doc->getSendMode(),
            'provider' => $doc->getProvider(),
            'sunat_mode' => $doc->getSunatMode(),
            'sunat_code' => $doc->getSunatCode(),
            'sunat_message' => $doc->getSunatMessage(),
            'customer_name' => $customerName,
            'company_ruc' => $companyRuc,
            'company_name' => $companyName,
            'company_environment' => $empresa?->getAmbiente(),
            'total' => $total,
            'customer_email' => $doc->getCustomerEmail(),
            'email_status' => $doc->getEmailStatus(),
            'retry_count' => $doc->getRetryCount(),
            'created_at' => $doc->getCreatedAt()->format(DATE_ATOM),
            'accepted_at' => $doc->getAcceptedAt() ? $doc->getAcceptedAt()->format(DATE_ATOM) : null,
        ];
    }
}
