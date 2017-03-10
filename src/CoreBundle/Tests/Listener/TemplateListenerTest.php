<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Tests\Listener;

use CSBill\CoreBundle\Templating\Template;
use CSBill\CoreBundle\Listener\TemplateListener;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;

class TemplateListenerTest extends TestCase
{
    public function testOnKernelView()
    {
        $twig = M::mock(\Twig_Environment::class);

        $twig->shouldReceive('render')
            ->once()
            ->with('Foo', ['bar' => 'baz'])
            ->andReturn('foo bar baz');

        $listener = new TemplateListener($twig);

        $event = new GetResponseForControllerResultEvent(M::mock(HttpKernelInterface::class), Request::createFromGlobals(), Kernel::MASTER_REQUEST, new Template('Foo', ['bar' => 'baz']));

        $listener->onKernelView($event);

        $this->assertInstanceOf(Response::class, $event->getResponse());
        $this->assertSame('foo bar baz', $event->getResponse()->getContent());
    }

    public function testOnKernelViewWithoutResponse()
    {
        $twig = M::mock(\Twig_Environment::class);

        $twig->shouldReceive('render')
            ->never();

        $listener = new TemplateListener($twig);

        $event = new GetResponseForControllerResultEvent(M::mock(HttpKernelInterface::class), Request::createFromGlobals(), Kernel::MASTER_REQUEST, []);

        $listener->onKernelView($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelViewWithCustomResponse()
    {
        $twig = M::mock(\Twig_Environment::class);
        $response = new Response();
        $response->headers->add(['one' => 'two']);

        $twig->shouldReceive('render')
            ->once()
            ->with('Foo', ['bar' => 'baz'])
            ->andReturn('foo bar baz');

        $listener = new TemplateListener($twig);

        $event = new GetResponseForControllerResultEvent(M::mock(HttpKernelInterface::class), Request::createFromGlobals(), Kernel::MASTER_REQUEST, new Template('Foo', ['bar' => 'baz'], $response));

        $listener->onKernelView($event);

        $this->assertSame($response, $event->getResponse());
        $this->assertSame('foo bar baz', $event->getResponse()->getContent());
    }
}
