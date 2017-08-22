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

namespace SolidInvoice\ApiBundle\Tests\Event\Listener;

use ApiPlatform\Core\EventListener\EventPriorities;
use SolidInvoice\ApiBundle\Event\Listener\QuoteCreateListener;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Model\Graph;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Workflow\StateMachine;

class QuoteCreateListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testSubscribedEvents()
    {
        $this->assertSame([KernelEvents::VIEW], array_keys(QuoteCreateListener::getSubscribedEvents()));
        $this->assertSame(EventPriorities::PRE_WRITE, QuoteCreateListener::getSubscribedEvents()[KernelEvents::VIEW][0][1]);
    }

    public function testStatusGetsUpdated()
    {
        $entity = new Quote();

        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('apply')
            ->once()
            ->with($entity, Graph::TRANSITION_NEW);

        $listener = new QuoteCreateListener($stateMachine);
        $request = Request::create('/', 'POST');
        $listener->setQuoteStatus(new GetResponseForControllerResultEvent(M::mock(KernelInterface::class), $request, Kernel::MASTER_REQUEST, $entity));
    }

    public function testSkipIfNotMasterRequest()
    {
        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('apply')
            ->never();

        $listener = new QuoteCreateListener($stateMachine);
        $request = Request::create('/', 'POST');
        $listener->setQuoteStatus(new GetResponseForControllerResultEvent(M::mock(KernelInterface::class), $request, Kernel::SUB_REQUEST, new Quote()));
    }

    public function testSkipIfQuoteAlreadyHasAStatus()
    {
        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('apply')
            ->never();

        $listener = new QuoteCreateListener($stateMachine);
        $request = Request::create('/', 'POST');
        $entity = new Quote();
        $entity->setStatus(Graph::STATUS_DRAFT);

        $listener->setQuoteStatus(new GetResponseForControllerResultEvent(M::mock(KernelInterface::class), $request, Kernel::MASTER_REQUEST, $entity));
    }

    public function testSkipIfNoQuoteIsPassed()
    {
        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('apply')
            ->never();

        $listener = new QuoteCreateListener($stateMachine);
        $request = Request::create('/', 'POST');

        $listener->setQuoteStatus(new GetResponseForControllerResultEvent(M::mock(KernelInterface::class), $request, Kernel::MASTER_REQUEST, new Invoice()));
    }

    public function testSkipIfNotPostRequest()
    {
        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('apply')
            ->never();

        $listener = new QuoteCreateListener($stateMachine);
        $request = Request::create('/', 'GET');

        $listener->setQuoteStatus(new GetResponseForControllerResultEvent(M::mock(KernelInterface::class), $request, Kernel::MASTER_REQUEST, new Quote()));
    }
}
