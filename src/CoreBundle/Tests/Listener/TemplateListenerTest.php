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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Listener\TemplateListener;
use SolidInvoice\CoreBundle\Templating\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class TemplateListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testOnKernelView()
    {
        $twig = new Environment(new ArrayLoader(['Foo' => 'foo bar baz']));

        $listener = new TemplateListener($twig);

        $event = new ViewEvent(M::mock(HttpKernelInterface::class), Request::createFromGlobals(), Kernel::MASTER_REQUEST, new Template('Foo', ['bar' => 'baz']));

        $listener->onKernelView($event);

        static::assertInstanceOf(Response::class, $event->getResponse());
        static::assertSame('foo bar baz', $event->getResponse()->getContent());
    }

    public function testOnKernelViewWithoutResponse()
    {
        $twig = new Environment(new ArrayLoader());

        $listener = new TemplateListener($twig);

        $event = new ViewEvent(M::mock(HttpKernelInterface::class), Request::createFromGlobals(), Kernel::MASTER_REQUEST, []);

        $listener->onKernelView($event);

        static::assertNull($event->getResponse());
    }

    public function testOnKernelViewWithCustomResponse()
    {
        $twig = new Environment(new ArrayLoader(['Foo' => 'foo bar baz']));
        $response = new Response();
        $response->headers->add(['one' => 'two']);

        $listener = new TemplateListener($twig);

        $event = new ViewEvent(M::mock(HttpKernelInterface::class), Request::createFromGlobals(), Kernel::MASTER_REQUEST, new Template('Foo', ['bar' => 'baz'], $response));

        $listener->onKernelView($event);

        static::assertSame($response, $event->getResponse());
        static::assertSame('foo bar baz', $event->getResponse()->getContent());
    }
}
