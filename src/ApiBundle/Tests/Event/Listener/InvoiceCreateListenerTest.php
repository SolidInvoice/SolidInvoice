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

namespace SolidInvoice\ApiBundle\Tests\Event\Listener;

use ApiPlatform\Core\EventListener\EventPriorities;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ApiBundle\Event\Listener\InvoiceCreateListener;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Workflow\StateMachine;

class InvoiceCreateListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testSubscribedEvents()
    {
        self::assertSame([KernelEvents::VIEW], array_keys(InvoiceCreateListener::getSubscribedEvents()));
        self::assertSame(EventPriorities::PRE_WRITE, InvoiceCreateListener::getSubscribedEvents()[KernelEvents::VIEW][0][1]);
    }

    public function testStatusGetsUpdated()
    {
        $entity = new Invoice();

        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('apply')
            ->once()
            ->with($entity, Graph::TRANSITION_NEW);

        $listener = new InvoiceCreateListener($stateMachine);
        $request = Request::create('/', Request::METHOD_POST);
        $listener->setInvoiceStatus(new ViewEvent(M::mock(KernelInterface::class), $request, Kernel::MASTER_REQUEST, $entity));
    }

    public function testSkipIfNotMasterRequest()
    {
        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('apply')
            ->never();

        $listener = new InvoiceCreateListener($stateMachine);
        $request = Request::create('/', Request::METHOD_POST);
        $listener->setInvoiceStatus(new ViewEvent(M::mock(KernelInterface::class), $request, Kernel::SUB_REQUEST, new Invoice()));
    }

    public function testSkipIfInvoiceAlreadyHasAStatus()
    {
        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('apply')
            ->never();

        $listener = new InvoiceCreateListener($stateMachine);
        $request = Request::create('/', Request::METHOD_POST);
        $entity = new Invoice();
        $entity->setStatus(Graph::STATUS_DRAFT);

        $listener->setInvoiceStatus(new ViewEvent(M::mock(KernelInterface::class), $request, Kernel::MASTER_REQUEST, $entity));
    }

    public function testSkipIfNoInvoiceIsPassed()
    {
        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('apply')
            ->never();

        $listener = new InvoiceCreateListener($stateMachine);
        $request = Request::create('/', Request::METHOD_POST);

        $listener->setInvoiceStatus(new ViewEvent(M::mock(KernelInterface::class), $request, Kernel::MASTER_REQUEST, new Quote()));
    }

    public function testSkipIfNotPostRequest()
    {
        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('apply')
            ->never();

        $listener = new InvoiceCreateListener($stateMachine);
        $request = Request::create('/', Request::METHOD_GET);

        $listener->setInvoiceStatus(new ViewEvent(M::mock(KernelInterface::class), $request, Kernel::MASTER_REQUEST, new Invoice()));
    }
}
