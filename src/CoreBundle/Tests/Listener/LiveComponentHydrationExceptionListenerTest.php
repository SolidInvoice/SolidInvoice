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

namespace SolidInvoice\CoreBundle\Tests\Listener;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SolidInvoice\CoreBundle\Listener\LiveComponentHydrationExceptionListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\UX\LiveComponent\Exception\HydrationException;

#[CoversClass(LiveComponentHydrationExceptionListener::class)]
final class LiveComponentHydrationExceptionListenerTest extends TestCase
{
    public function testSubscribesToKernelExceptionAtPriority64(): void
    {
        $events = LiveComponentHydrationExceptionListener::getSubscribedEvents();

        self::assertArrayHasKey(KernelEvents::EXCEPTION, $events);
        self::assertSame(['onKernelException', 64], $events[KernelEvents::EXCEPTION]);
    }

    public function testHydrationExceptionWithRefererRedirectsToReferer(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::never())->method('generate');

        $listener = new LiveComponentHydrationExceptionListener($router);

        $request = Request::create('/_components/CreateInvoice/saveUpdate', 'POST');
        $request->headers->set('Referer', 'https://example.com/invoices/create');
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $event = new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new HydrationException('Invalid checksum sent when updating the live component.'),
        );

        $listener->onKernelException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('https://example.com/invoices/create', $response->getTargetUrl());
        self::assertTrue($event->isPropagationStopped());
        self::assertSame(['live_component.session_expired'], $session->getFlashBag()->get('error'));
    }

    public function testHydrationExceptionWithoutRefererRedirectsToHome(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::once())
            ->method('generate')
            ->with('_home')
            ->willReturn('/');

        $listener = new LiveComponentHydrationExceptionListener($router);

        $request = Request::create('/_components/CreateInvoice/saveUpdate', 'POST');
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $event = new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new HydrationException('Invalid checksum sent when updating the live component.'),
        );

        $listener->onKernelException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/', $response->getTargetUrl());
        self::assertTrue($event->isPropagationStopped());
        self::assertSame(['live_component.session_expired'], $session->getFlashBag()->get('error'));
    }

    public function testOtherExceptionsAreIgnored(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::never())->method('generate');

        $listener = new LiveComponentHydrationExceptionListener($router);

        $request = Request::create('/some-page', 'GET');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $event = new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new RuntimeException('Unrelated exception'),
        );

        $listener->onKernelException($event);

        self::assertNull($event->getResponse());
        self::assertFalse($event->isPropagationStopped());
    }
}
