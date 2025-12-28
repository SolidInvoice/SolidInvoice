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

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use function in_array;

/**
 * @see \SolidInvoice\InstallBundle\Tests\Listener\RequestListenerTest
 */
final class RequestListener implements EventSubscriberInterface, ServiceSubscriberInterface
{
    public const string INSTALLER_ROUTE = '_system_install';

    public static bool $isDebug = false;

    /**
     * Core routes.
     *
     * @var list<string>
     */
    private array $allowRoutes = [
        self::INSTALLER_ROUTE,
        'ux_live_component',
    ];

    /**
     * @var list<string>
     */
    private const array DEBUG_ROUTES = [
        '_wdt',
        '_wdt_stylesheet',
        '_profiler',
        '_profiler_search',
        '_profiler_search_bar',
        '_profiler_search_results',
        '_profiler_router',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function __construct(
        private readonly RouterInterface $router,
        private readonly ContainerInterface $locator,
        private readonly ?string $installed,
        private readonly bool $debug = false
    ) {
        if ($this->debug) {
            $this->allowRoutes = array_merge($this->allowRoutes, self::DEBUG_ROUTES);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (! $this->installed) {
            if (null === $route || ! in_array($route, $this->allowRoutes, true)) {
                $this->redirectToRoute($event, self::INSTALLER_ROUTE);
            }

            $session = $request->getSession();

            if (! $session->isStarted()) {
                $session->start();
            }

            $_SERVER['SOLIDINVOICE_APP_SECRET'] = $_ENV['SOLIDINVOICE_APP_SECRET'] = $request->getSession()->getId();

            if ($this->locator->has(ContainerInterface::class)) {
                $container = $this->locator->get(ContainerInterface::class);
                if ($container instanceof Container) {
                    $container->resetEnvCache();
                }
            }
        }
    }

    private function redirectToRoute(RequestEvent $event, string $route): void
    {
        $response = new RedirectResponse($this->router->generate($route));

        $event->setResponse($response);
        $event->stopPropagation();
    }

    public static function getSubscribedServices(): array
    {
        return [
            ContainerInterface::class => 'service_container',
        ];
    }
}
