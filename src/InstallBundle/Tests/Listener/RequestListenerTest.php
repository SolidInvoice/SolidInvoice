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

namespace SolidInvoice\InstallBundle\Tests\Listener;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use SolidInvoice\InstallBundle\Listener\RequestListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;
use function date;

/** @covers \SolidInvoice\InstallBundle\Listener\RequestListener */
final class RequestListenerTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        self::assertSame(
            [
                'kernel.request' => ['onKernelRequest', 10],
            ],
            RequestListener::getSubscribedEvents()
        );
    }

    public function testItRedirectsToTheInstallationIfTheApplicationIsNotInstalled(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects(self::once())
            ->method('generate')
            ->with(RequestListener::INSTALLER_ROUTE)
            ->willReturn('/install');

        $listener = new RequestListener(
            $router,
            $this->createMock(ContainerInterface::class),
            null,
        );

        $request = Request::createFromGlobals();
        $request->setSession(new Session(new MockArraySessionStorage()));

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        self::assertNull($event->getResponse());

        $listener->onKernelRequest($event);

        $response = $event->getResponse();

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/install', $response->getTargetUrl());
        self::assertTrue($event->isPropagationStopped());
    }

    public function testItRedirectsToTheInstallationIfTheApplicationIsNotInstalledAndRequestingDebugRoute(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects(self::once())
            ->method('generate')
            ->with(RequestListener::INSTALLER_ROUTE)
            ->willReturn('/install');

        $listener = new RequestListener(
            $router,
            $this->createMock(ContainerInterface::class),
            null,
        );

        $request = Request::createFromGlobals();
        $request->attributes->set('_route', '_profiler');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        self::assertNull($event->getResponse());

        $listener->onKernelRequest($event);

        $response = $event->getResponse();

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/install', $response->getTargetUrl());
        self::assertTrue($event->isPropagationStopped());
    }

    public function testItContinuesExecutionIfTheApplicationIsNotInstalledAndRequestingInstallerRoute(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects(self::never())
            ->method('generate');

        $listener = new RequestListener(
            $router,
            $this->createMock(ContainerInterface::class),
            null,
        );

        $request = Request::createFromGlobals();
        $request->attributes->set('_route', RequestListener::INSTALLER_ROUTE);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        self::assertNull($event->getResponse());

        $listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
        self::assertFalse($event->isPropagationStopped());
    }

    public function testItContinuesExecutionIfTheApplicationIsNotInstalledAndRequestingDebugRoute(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects(self::never())
            ->method('generate');

        $listener = new RequestListener(
            $router,
            $this->createMock(ContainerInterface::class),
            null,
            true,
        );

        $request = Request::createFromGlobals();
        $request->attributes->set('_route', '_profiler');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        self::assertNull($event->getResponse());

        $listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
        self::assertFalse($event->isPropagationStopped());
    }

    public function testItContinuesExecutionWhenApplicationIsInstalled(): void
    {
        $router = $this->createMock(RouterInterface::class);

        $router
            ->expects(self::never())
            ->method('generate');

        $listener = new RequestListener(
            $router,
            $this->createMock(ContainerInterface::class),
            date('Y-m-d H:i:s'),
        );

        $request = Request::createFromGlobals();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->attributes->set('_route', '_home');

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        self::assertNull($event->getResponse());

        $listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
        self::assertFalse($event->isPropagationStopped());
    }

    public function testItSetsSessionIdAsAppSecretWhenNotInstalled(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects(self::never())
            ->method('generate');

        $listener = new RequestListener(
            $router,
            $this->createMock(ContainerInterface::class),
            null,
        );

        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $sessionId = $session->getId();

        $request = Request::createFromGlobals();
        $request->setSession($session);
        $request->attributes->set('_route', RequestListener::INSTALLER_ROUTE);

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);

        self::assertSame($sessionId, $_SERVER['SOLIDINVOICE_APP_SECRET']);
        self::assertSame($sessionId, $_ENV['SOLIDINVOICE_APP_SECRET']);
        self::assertNull($event->getResponse());
        self::assertFalse($event->isPropagationStopped());
    }

    public function testItContinuesExecutionOnSubRequest(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects(self::never())
            ->method('generate');

        $listener = new RequestListener(
            $router,
            $this->createMock(ContainerInterface::class),
            null,
        );

        $request = Request::createFromGlobals();
        $request->setSession(new Session(new MockArraySessionStorage()));

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::SUB_REQUEST);

        $listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
        self::assertFalse($event->isPropagationStopped());
    }

    public function testItAllowsLiveComponentRouteWhenNotInstalled(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects(self::never())
            ->method('generate');

        $listener = new RequestListener(
            $router,
            $this->createMock(ContainerInterface::class),
            null,
        );

        $request = Request::createFromGlobals();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->attributes->set('_route', 'ux_live_component');

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
        self::assertFalse($event->isPropagationStopped());
    }
}
