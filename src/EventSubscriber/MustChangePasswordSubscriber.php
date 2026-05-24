<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\AdminUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Obliga a cambiar contraseña por defecto antes de usar el dashboard.
 */
class MustChangePasswordSubscriber implements EventSubscriberInterface
{
    private Security $security;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(Security $security, UrlGeneratorInterface $urlGenerator)
    {
        $this->security = $security;
        $this->urlGenerator = $urlGenerator;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof AdminUser || !$user->isMustChangePassword()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();
        $allowed = ['/change-password', '/logout', '/login'];
        foreach ($allowed as $prefix) {
            if ($path === $prefix || strpos($path, $prefix . '/') === 0) {
                return;
            }
        }

        $event->setResponse(new RedirectResponse(
            $this->urlGenerator->generate('admin_change_password')
        ));
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 8]];
    }
}
