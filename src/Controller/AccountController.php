<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AdminUser;
use App\Util\FiscalUiAssets;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AccountController extends AbstractController
{
    #[Route('/change-password', name: 'admin_change_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof AdminUser) {
            throw new AccessDeniedException();
        }

        $error = null;
        if ($request->isMethod('POST')) {
            $current = (string) $request->request->get('current_password', '');
            $new = (string) $request->request->get('new_password', '');
            $confirm = (string) $request->request->get('confirm_password', '');

            if (!$hasher->isPasswordValid($user, $current)) {
                $error = 'La contraseña actual no es correcta.';
            } elseif (strlen($new) < 10) {
                $error = 'La nueva contraseña debe tener al menos 10 caracteres.';
            } elseif ($new !== $confirm) {
                $error = 'La confirmación no coincide.';
            } elseif ($hasher->isPasswordValid($user, $new)) {
                $error = 'La nueva contraseña debe ser diferente a la actual.';
            } else {
                $user->setPassword($hasher->hashPassword($user, $new));
                $user->setMustChangePassword(false);
                $em->flush();
                $this->addFlash('success', 'Contraseña actualizada correctamente.');

                return $this->redirectToRoute('fiscal_dashboard');
            }
        }

        $required = $user->isMustChangePassword();
        return new Response($this->renderPage($error, $required), 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    private function renderPage(?string $error, bool $required): string
    {
        $errorHtml = $error !== null
            ? '<p class="alert-err">' . htmlspecialchars($error, ENT_QUOTES) . '</p>'
            : '';
        $notice = $required
            ? '<p class="alert-warn">Debe cambiar la contraseña por defecto antes de continuar.</p>'
            : '';
        $css = htmlspecialchars(FiscalUiAssets::stylesheetHref(), ENT_QUOTES);

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cambiar contraseña</title>
  <link rel="stylesheet" href="{$css}">
</head>
<body class="fiscal-ui">
  <div class="auth-wrap">
    <form class="auth-card" method="post">
      <div class="auth-brand">
        <div class="auth-logo">🔒</div>
        <div>
          <h1>Cambiar contraseña</h1>
          <p class="auth-sub">Mantenga segura la cuenta del facturador</p>
        </div>
      </div>
      {$notice}{$errorHtml}
      <label>Contraseña actual</label>
      <input name="current_password" type="password" required autocomplete="current-password">
      <label>Nueva contraseña (mín. 10 caracteres)</label>
      <input name="new_password" type="password" required minlength="10" autocomplete="new-password">
      <label>Confirmar nueva contraseña</label>
      <input name="confirm_password" type="password" required minlength="10" autocomplete="new-password">
      <button type="submit" class="btn-primary">Guardar contraseña</button>
      <p class="auth-hint"><a href="/dashboard" style="color:var(--primary)">Volver al dashboard</a></p>
    </form>
  </div>
</body>
</html>
HTML;
    }
}
