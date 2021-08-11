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
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

/**
 * Listener class to intercept requests
 * and redirect to the installer if necessary.
 */
class RequestListener implements EventSubscriberInterface
{
    public const INSTALLER_ROUTE = '_install_check_requirements';

    /**
     * Core routes.
     *
     * @var array
     */
    private $allowRoutes = [];

    private $installRoutes = [
        self::INSTALLER_ROUTE,
        '_install_config',
        '_install_install',
        '_install_setup',
        '_install_finish',
    ];

    /**
     * @var array
     */
    private $debugRoutes = [
        '_wdt',
        '_profiler',
        '_profiler_search',
        '_profiler_search_bar',
        '_profiler_search_results',
        '_profiler_router',
    ];

    /**
     * @var string
     */
    private $installed;

    /**
     * @var Router
     */
    private $router;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function __construct(?string $installed, RouterInterface $router, bool $debug = false)
    {
        $this->installed = $installed;
        $this->router = $router;
        $this->allowRoutes += $this->installRoutes;

        if ($debug) {
            $this->allowRoutes = array_merge($this->allowRoutes, $this->debugRoutes);
        }
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (HttpKernel::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $route = $event->getRequest()->get('_route');

        if (null !== $this->installed) {
            if (in_array($route, $this->installRoutes, true)) {
                throw new ApplicationInstalledException();
            }
        } elseif (!in_array($route, $this->allowRoutes, true)) {
            $response = new RedirectResponse($this->router->generate(self::INSTALLER_ROUTE));

            $event->setResponse($response);
            $event->stopPropagation();
        }
    }
}
