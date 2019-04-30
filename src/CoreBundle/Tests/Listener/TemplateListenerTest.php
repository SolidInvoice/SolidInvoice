<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Tests\Listener;

use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\CoreBundle\Listener\TemplateListener;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;

class TemplateListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testOnKernelView()
    {
        $twig = M::mock(\Twig\Environment::class);

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
        $twig = M::mock(\Twig\Environment::class);

        $twig->shouldReceive('render')
            ->never();

        $listener = new TemplateListener($twig);

        $event = new GetResponseForControllerResultEvent(M::mock(HttpKernelInterface::class), Request::createFromGlobals(), Kernel::MASTER_REQUEST, []);

        $listener->onKernelView($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelViewWithCustomResponse()
    {
        $twig = M::mock(\Twig\Environment::class);
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
