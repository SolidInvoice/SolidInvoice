<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\UX\LiveComponent\Exception\HydrationException;

/**
 * Converts a LiveComponent HydrationException (invalid checksum) into a
 * user-friendly redirect instead of a 500 error page.
 *
 * This typically occurs when APP_SECRET rotates on redeployment or the
 * user's session expires while a Live Component form is open.
 */
final class LiveComponentHydrationExceptionListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly RouterInterface $router,
    ) {
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 64],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (! $event->getThrowable() instanceof HydrationException) {
            return;
        }

        $request = $event->getRequest();
        $session = $request->getSession();

        if ($session instanceof Session) {
            $session->getFlashBag()->add('error', 'live_component.session_expired');
        }

        $referer = $request->headers->get('Referer');
        $url = ($referer !== null && $referer !== '') ? $referer : $this->router->generate('_home');

        $event->setResponse(new RedirectResponse($url));
        $event->stopPropagation();
    }
}
