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

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SolidInvoice\CoreBundle\Listener\LiveComponentExceptionListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

#[CoversClass(LiveComponentExceptionListener::class)]
final class LiveComponentExceptionListenerTest extends TestCase
{
    private LiveComponentExceptionListener $listener;

    protected function setUp(): void
    {
        $this->listener = new LiveComponentExceptionListener();
    }

    public function testSubscribesToKernelException(): void
    {
        $events = LiveComponentExceptionListener::getSubscribedEvents();

        self::assertArrayHasKey(KernelEvents::EXCEPTION, $events);
        self::assertSame('onKernelException', $events[KernelEvents::EXCEPTION]);
    }

    public function testConvertsInvalidArgumentExceptionOnLiveComponentRouteToNotFound(): void
    {
        $request = Request::create('/_components/CreateInvoice/Send', 'POST');
        $request->attributes->set('_route', 'ux_live_component');

        $original = new InvalidArgumentException(
            'Expected method "Send" on class "SolidInvoice\InvoiceBundle\Twig\Components\CreateInvoice", did you mean "saveSend"?'
        );

        $event = $this->createEvent($request, $original);

        $this->listener->onKernelException($event);

        $thrown = $event->getThrowable();
        self::assertInstanceOf(NotFoundHttpException::class, $thrown);
        self::assertSame($original, $thrown->getPrevious());
    }

    public function testIgnoresNonLiveComponentRoute(): void
    {
        $request = Request::create('/some-other-route');
        $request->attributes->set('_route', 'some_other_route');

        $original = new InvalidArgumentException('some error');
        $event = $this->createEvent($request, $original);

        $this->listener->onKernelException($event);

        self::assertSame($original, $event->getThrowable());
    }

    public function testIgnoresNonInvalidArgumentException(): void
    {
        $request = Request::create('/_components/CreateInvoice/Send', 'POST');
        $request->attributes->set('_route', 'ux_live_component');

        $original = new RuntimeException('some other error');
        $event = $this->createEvent($request, $original);

        $this->listener->onKernelException($event);

        self::assertSame($original, $event->getThrowable());
    }

    public function testIgnoresLiveComponentRouteWithoutRouteAttribute(): void
    {
        $request = Request::create('/_components/CreateInvoice/Send', 'POST');

        $original = new InvalidArgumentException('Expected method "Send"...');
        $event = $this->createEvent($request, $original);

        $this->listener->onKernelException($event);

        self::assertSame($original, $event->getThrowable());
    }

    private function createEvent(Request $request, \Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
    }
}
