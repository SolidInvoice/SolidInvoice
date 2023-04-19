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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExceptionListener implements EventSubscriberInterface
{
    private FlashBagInterface $flashBag;

    private TranslatorInterface $translator;

    private RouterInterface $router;

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function __construct(FlashBagInterface $flashBag, TranslatorInterface $translator, RouterInterface $router)
    {
        $this->flashBag = $flashBag;
        $this->translator = $translator;
        $this->router = $router;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof ApplicationInstalledException) {
            $this->flashBag->add('error', $this->translator->trans($exception->getMessage()));

            $event->setResponse(new RedirectResponse($this->router->generate('_home')));
            $event->stopPropagation();
        }
    }
}
