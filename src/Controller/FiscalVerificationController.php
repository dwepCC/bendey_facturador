<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\FiscalDocumentRepository;
use App\Service\Fiscal\Verification\FiscalSunatVerifier;
use App\Service\Fiscal\Verification\VerificationApplyException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * «Verificar en SUNAT» — SOLO para el operador del panel del facturador.
 *
 * Vive bajo /dashboard (access_control exige ROLE_ADMIN con sesión) y NO bajo /api/v1/fiscal: allí
 * también entra el CLIENT_TOKEN de backend_go, y por sus proxies el tenant podría llegar a la acción.
 * El tenant no consulta ni cambia el estado fiscal; se entera por el webhook cuando el operador aplica.
 */
class FiscalVerificationController
{
    public function __construct(
        private readonly FiscalDocumentRepository $repo,
        private readonly FiscalSunatVerifier $verifier,
        private readonly Security $security,
    ) {
    }

    /** Consulta y devuelve lo que respondió SUNAT. No escribe nada. */
    #[Route('/dashboard/api/documents/{uuid}/verify', name: 'fiscal_panel_verify', methods: ['POST'])]
    public function verify(string $uuid, Request $request): JsonResponse
    {
        if (($denied = $this->guard($request)) !== null) {
            return $denied;
        }
        $doc = $this->repo->findOneBy(['documentUuid' => $uuid]);
        if ($doc === null) {
            return new JsonResponse(['error' => 'Documento no encontrado'], 404);
        }

        $result = $this->verifier->verify($doc);

        return new JsonResponse($result->toArray() + [
            'current_status' => $doc->getStatus(),
            'applicable_from_current' => in_array($doc->getStatus(), FiscalSunatVerifier::APPLICABLE_FROM, true),
        ]);
    }

    /** Aplica el resultado tras la confirmación del operador. Vuelve a consultar antes de escribir. */
    #[Route('/dashboard/api/documents/{uuid}/verify/apply', name: 'fiscal_panel_verify_apply', methods: ['POST'])]
    public function apply(string $uuid, Request $request): JsonResponse
    {
        if (($denied = $this->guard($request)) !== null) {
            return $denied;
        }
        $doc = $this->repo->findOneBy(['documentUuid' => $uuid]);
        if ($doc === null) {
            return new JsonResponse(['error' => 'Documento no encontrado'], 404);
        }
        $body = json_decode((string) $request->getContent(), true);
        $expected = is_array($body) ? (string) ($body['expected_outcome'] ?? '') : '';
        if ($expected === '') {
            return new JsonResponse(['error' => 'Falta expected_outcome (el resultado que vio el operador).'], 400);
        }

        $operator = (string) ($this->security->getUser()?->getUserIdentifier() ?? 'admin');
        try {
            $result = $this->verifier->apply($doc, $expected, $operator);
        } catch (VerificationApplyException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'verification' => $e->result?->toArray()], 409);
        }

        return new JsonResponse([
            'applied' => true,
            'status' => $doc->getStatus(),
            'sunat_code' => $doc->getSunatCode(),
            'sunat_message' => $doc->getSunatMessage(),
            'verification' => $result->toArray(),
        ]);
    }

    /**
     * Sesión de admin (ya la exige access_control, se revisa igual) y una cabecera propia del panel:
     * un formulario de otro sitio no puede enviarla, así que un POST cruzado no dispara la acción.
     */
    private function guard(Request $request): ?JsonResponse
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Solo el operador del panel del facturador.'], 403);
        }
        if ($request->headers->get('X-Fiscal-Panel') !== '1') {
            return new JsonResponse(['error' => 'Acción disponible solo desde el panel del facturador.'], 403);
        }

        return null;
    }
}
