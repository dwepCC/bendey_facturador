<?php

declare(strict_types=1);

namespace App\Controller;

use App\Util\FiscalUiAssets;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'admin_login')]
    public function login(AuthenticationUtils $authenticationUtils, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('fiscal_dashboard');
        }

        return new Response($this->renderLoginPage(
            $authenticationUtils->getLastAuthenticationError(),
            $authenticationUtils->getLastUsername(),
            $csrfTokenManager->getToken('authenticate')->getValue()
        ), 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    #[Route('/logout', name: 'admin_logout')]
    public function logout(): void
    {
        throw new \LogicException('Logout gestionado por Symfony Security.');
    }

  private function renderLoginPage(?\Throwable $error, string $lastUsername, string $csrfToken): string
    {
        $errorHtml = '';
        if ($error !== null) {
            $errorHtml = '<p class="alert-err">' . htmlspecialchars($error->getMessage(), ENT_QUOTES) . '</p>';
        }

        $user = htmlspecialchars($lastUsername, ENT_QUOTES);
        $token = htmlspecialchars($csrfToken, ENT_QUOTES);
        $css = htmlspecialchars(FiscalUiAssets::stylesheetHref(), ENT_QUOTES);

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Acceso — Facturador fiscal</title>
  <link rel="stylesheet" href="{$css}">
</head>
<body class="fiscal-ui">
  <div class="auth-wrap">
    <form class="auth-card" method="post" action="/login">
      <div class="auth-brand">
        <div class="auth-logo">TF</div>
        <div>
          <h1>Facturador fiscal</h1>
          <p class="auth-sub">Panel de operaciones SUNAT / PSE</p>
        </div>
      </div>
      {$errorHtml}
      <input type="hidden" name="_csrf_token" value="{$token}">
      <label for="username">Usuario</label>
      <input id="username" name="_username" value="{$user}" required autocomplete="username">
      <label for="password">Contraseña</label>
      <input id="password" name="_password" type="password" required autocomplete="current-password">
      <button type="submit" class="btn-primary">Ingresar</button>
      <p class="auth-hint">El token <code>CLIENT_TOKEN</code> es solo para la API del ERP (<code>backend_go</code>), no para acceso web.</p>
    </form>
  </div>
</body>
</html>
HTML;
    }
}
