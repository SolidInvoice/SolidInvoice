<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Listener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Router;

/**
 * Listener class to intercept requests
 * and redirect to the installer if necessary.
 */
class RequestListener
{
    const INSTALLER_ROUTE = '_install_flow';

    /**
     * Core routes.
     *
     * @var array
     */
    private $allowRoutes = array(
        self::INSTALLER_ROUTE,
        'sylius_flow_display',
        'sylius_flow_forward',
        'fos_js_routing_js',
    );

    /**
     * @var array
     */
    private $debugRoutes = array(
        '_wdt',
        '_profiler',
        '_profiler_search',
        '_profiler_search_bar',
        '_profiler_search_results',
        '_profiler_router',
    );

    /**
     * @var string
     */
    private $installed;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @param string $installed
     * @param Router $router
     * @param bool   $debug
     */
    public function __construct($installed, Router $router, $debug = false)
    {
        $this->installed = $installed;
        $this->router = $router;
        $this->debug = $debug;

        if (true === $this->debug) {
            $this->allowRoutes = array_merge($this->allowRoutes, $this->debugRoutes);
        }
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($this->installed || $event->getRequestType() !== HttpKernel::MASTER_REQUEST) {
            return;
        }

        $route = $event->getRequest()->get('_route');

        if (!in_array($route, $this->allowRoutes)) {
            if ($this->debug && false !== strpos($route, '_assetic')) {
                return;
            }

            $response = new RedirectResponse($this->router->generate(self::INSTALLER_ROUTE));

            $event->setResponse($response);
            $event->stopPropagation();
        }
    }
}
