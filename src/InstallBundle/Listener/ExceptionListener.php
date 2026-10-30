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

namespace SolidInvoice\InstallBundle\Listener;

use SolidInvoice\InstallBundle\Exception\ApplicationInstalledException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExceptionListener
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly RouterInterface $router
    ) {
    }

    #[AsEventListener(event: KernelEvents::EXCEPTION)]
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof ApplicationInstalledException) {
            $session = $event->getRequest()
                ->getSession();
            assert($session instanceof Session);
            $session->getFlashBag()
                ->add('error', $this->translator->trans($exception->getMessage()));

            $event->setResponse(new RedirectResponse($this->router->generate('_home')));
            $event->stopPropagation();
        }
    }
}
