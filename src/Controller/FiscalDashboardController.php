<?php

declare(strict_types=1);

namespace App\Controller;

use App\Util\FiscalUiAssets;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Dashboard fiscal SaaS (UI minimalista mejorada).
 */
class FiscalDashboardController
{
    #[Route('/dashboard', name: 'fiscal_dashboard')]
    public function index(Request $request, Security $security): Response
    {
        $apiBase = $request->getSchemeAndHttpHost() . $request->getBasePath() . '/api/v1/fiscal';
        $user = $security->getUser();
        $username = $user !== null ? $user->getUserIdentifier() : '';
        $content = file_get_contents(__DIR__ . '/../../views/fiscal_dashboard.html');
        $content = str_replace(
            ['__API_BASE__', '__USERNAME__', '__FISCAL_CSS__', '__DASH_NAV__'],
            [
                htmlspecialchars($apiBase, ENT_QUOTES),
                htmlspecialchars($username, ENT_QUOTES),
                htmlspecialchars(FiscalUiAssets::stylesheetHref(), ENT_QUOTES),
                FiscalUiAssets::dashNavHtml('dashboard'),
            ],
            $content
        );
        return new Response($content, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
