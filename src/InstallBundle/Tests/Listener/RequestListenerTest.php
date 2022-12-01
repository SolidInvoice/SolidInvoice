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
use SolidInvoice\InstallBundle\Exception\ApplicationInstalledException;
use SolidInvoice\InstallBundle\Listener\RequestListener;
use SolidInvoice\UserBundle\Repository\UserRepositoryInterface;
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
            $this->createMock(UserRepositoryInterface::class),
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
            $this->createMock(UserRepositoryInterface::class),
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
            $this->createMock(UserRepositoryInterface::class),
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
            $this->createMock(UserRepositoryInterface::class),
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

    public function testItThrowsAnExceptionIfTheApplicationIsAlreadyInstalled(): void
    {
        $this->expectException(ApplicationInstalledException::class);

        $router = $this->createMock(RouterInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);

        $router
            ->expects(self::never())
            ->method('generate');

        $userRepository->expects(self::once())
            ->method('getUserCount')
            ->willReturn(1);

        $listener = new RequestListener(
            $router,
            $userRepository,
            date('Y-m-d H:i:s'),
        );

        $request = Request::createFromGlobals();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->attributes->set('_route', RequestListener::INSTALLER_ROUTE);

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        self::assertNull($event->getResponse());

        $listener->onKernelRequest($event);
    }

    public function testItContinuesExecutionWhenNotRequestingInstallRoute(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);

        $router
            ->expects(self::never())
            ->method('generate');

        $userRepository->expects(self::once())
            ->method('getUserCount')
            ->willReturn(1);

        $listener = new RequestListener(
            $router,
            $userRepository,
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

    public function testItRedirectsToTheSetupPageWhenNoUsersAreFound(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);

        $router
            ->expects(self::once())
            ->method('generate')
            ->with('_install_setup')
            ->willReturn('/install/setup');

        $userRepository->expects(self::once())
            ->method('getUserCount')
            ->willReturn(0);

        $listener = new RequestListener(
            $router,
            $userRepository,
            date('Y-m-d H:i:s'),
        );

        $request = Request::createFromGlobals();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->attributes->set('_route', '_home');

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        self::assertNull($event->getResponse());

        $listener->onKernelRequest($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/install/setup', $response->getTargetUrl());
        self::assertTrue($event->isPropagationStopped());
    }

    public function testItRedirectsToTheSetupPageWhenDebugIsDisabled(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);

        $router
            ->expects(self::once())
            ->method('generate')
            ->with('_install_setup')
            ->willReturn('/install/setup');

        $userRepository->expects(self::once())
            ->method('getUserCount')
            ->willReturn(0);

        $listener = new RequestListener(
            $router,
            $userRepository,
            date('Y-m-d H:i:s'),
        );

        $request = Request::createFromGlobals();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->attributes->set('_route', '_profiler');

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        self::assertNull($event->getResponse());

        $listener->onKernelRequest($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/install/setup', $response->getTargetUrl());
        self::assertTrue($event->isPropagationStopped());
    }

    public function testItContinuesExecutionWhenRequestingSetupRouteWithNoUsers(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);

        $router
            ->expects(self::never())
            ->method('generate');

        $userRepository->expects(self::once())
            ->method('getUserCount')
            ->willReturn(0);

        $listener = new RequestListener(
            $router,
            $userRepository,
            date('Y-m-d H:i:s'),
        );

        $request = Request::createFromGlobals();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->attributes->set('_route', '_install_setup');

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        self::assertNull($event->getResponse());

        $listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
        self::assertFalse($event->isPropagationStopped());
    }

    public function testItDoesNotRedirectToTheSetupPageWhenDebugIsEnabled(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);

        $router
            ->expects(self::never())
            ->method('generate');

        $userRepository->expects(self::once())
            ->method('getUserCount')
            ->willReturn(0);

        $listener = new RequestListener(
            $router,
            $userRepository,
            date('Y-m-d H:i:s'),
            true,
        );

        $request = Request::createFromGlobals();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->attributes->set('_route', '_profiler');

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        self::assertNull($event->getResponse());

        $listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
        self::assertFalse($event->isPropagationStopped());
    }
}
