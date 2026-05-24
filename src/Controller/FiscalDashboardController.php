<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

/**
 * Dashboard fiscal SaaS (UI minimalista mejorada).
 */
class FiscalDashboardController
{
    /**
     * @Route("/dashboard", name="fiscal_dashboard")
     */
    public function index(Request $request, Security $security): Response
    {
        $apiBase = $request->getSchemeAndHttpHost() . $request->getBasePath() . '/api/v1/fiscal';
        $user = $security->getUser();
        $username = $user !== null ? $user->getUserIdentifier() : '';
        $content = file_get_contents(__DIR__ . '/../../views/fiscal_dashboard.html');
        $content = str_replace(
            ['__API_BASE__', '__USERNAME__'],
            [htmlspecialchars($apiBase, ENT_QUOTES), htmlspecialchars($username, ENT_QUOTES)],
            $content
        );
        return new Response($content, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
