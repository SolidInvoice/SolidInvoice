<?php
/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\InstallBundle\Listener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use CSBill\InstallBundle\Installer\Installer;
use Symfony\Component\Routing\Router;

/**
 * Listener class to intercept requests
 * and redirect to the installer if necessary
 */
class RequestListener
{
    /**
     * Core paths for assets
     *
     * @var array $core_paths
     */
    protected $corePaths = array('css', 'images', 'js');

    /**
     * Core routes
     *
     * @var array $core_routes
     */
    protected $coreRoutes = array(
                                    Installer::INSTALLER_ROUTE,
                                    Installer::INSTALLER_SUCCESS_ROUTE,
                                    Installer::INSTALLER_RESTART_ROUTE,
                                    '_installer_step',
                                    '_profiler',
                                    '_wdt'
                                  );

    /**
     * @var Installer
     */
    protected $installer;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @param Installer $installer
     * @param Router    $router
     */
    public function __construct(Installer $installer, Router $router)
    {
        $this->installer = $installer;
        $this->router = $router;
    }

    /**
     * @param  GetResponseEvent $event
     * @return null
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->getRequestType() !== HttpKernel::MASTER_REQUEST) {
            return;
        }

        $route = $event->getRequest()->get('_route');

        $map = array_map(function ($route) use ($event) {
            return strpos($event->getRequest()->getPathInfo(), $route);
        }, $this->corePaths);

        if (!in_array($route, $this->coreRoutes) && !in_array(true, $map)) {
            if (!$this->installer->isInstalled()) {
                $response = new RedirectResponse($this->router->generate(Installer::INSTALLER_ROUTE));

                $event->setResponse($response);
            }

            return null;
        }
    }
}
