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

namespace SolidInvoice\MailerBundle\Tests\Listener;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SolidInvoice\MailerBundle\Listener\MailerTransportExceptionListener;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Mailer\Exception\TransportException;

class MailerTransportExceptionListenerTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $events = MailerTransportExceptionListener::getSubscribedEvents();

        self::assertArrayHasKey(KernelEvents::EXCEPTION, $events);
        self::assertSame(['onKernelException', 10], $events[KernelEvents::EXCEPTION]);
    }

    public function testHandlesTransportException(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                self::stringContains('Connection refused'),
                self::arrayHasKey('exception')
            );

        $listener = new MailerTransportExceptionListener($logger);

        $flashBag = new FlashBag();
        $session = $this->createMock(Session::class);
        $session->method('getFlashBag')->willReturn($flashBag);

        $request = Request::create('/invoices/1/send');
        $request->setSession($session);
        $request->headers->set('referer', '/invoices/1');

        $exception = new TransportException('Connection refused');
        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $listener->onKernelException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/invoices/1', $response->getTargetUrl());
        self::assertNotEmpty($flashBag->get('error'));
    }

    public function testHandlesWrappedTransportException(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        $listener = new MailerTransportExceptionListener($logger);

        $flashBag = new FlashBag();
        $session = $this->createMock(Session::class);
        $session->method('getFlashBag')->willReturn($flashBag);

        $request = Request::create('/quotes/1/send');
        $request->setSession($session);

        $transportException = new TransportException('SMTP server not responding');
        $wrappedException = new RuntimeException('Failed to send', 0, $transportException);

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $wrappedException
        );

        $listener->onKernelException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertNotEmpty($flashBag->get('error'));
    }

    public function testIgnoresNonTransportExceptions(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        $listener = new MailerTransportExceptionListener($logger);

        $request = Request::create('/some/page');

        $exception = new RuntimeException('Some other error');
        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $listener->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    public function testRedirectsToCurrentUrlWhenNoReferer(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        $listener = new MailerTransportExceptionListener($logger);

        $flashBag = new FlashBag();
        $session = $this->createMock(Session::class);
        $session->method('getFlashBag')->willReturn($flashBag);

        $request = Request::create('http://localhost/invoices/1/send');
        $request->setSession($session);

        $exception = new TransportException('Connection timed out');
        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $listener->onKernelException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('http://localhost/invoices/1/send', $response->getTargetUrl());
    }
}
