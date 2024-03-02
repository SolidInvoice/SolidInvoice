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

use ApiPlatform\Symfony\EventListener\EventPriorities;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ApiBundle\Event\Listener\QuoteCreateListener;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Model\Graph;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Workflow\StateMachine;

class QuoteCreateListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testSubscribedEvents(): void
    {
        self::assertSame([KernelEvents::VIEW], array_keys(QuoteCreateListener::getSubscribedEvents()));
        self::assertSame(EventPriorities::PRE_WRITE, QuoteCreateListener::getSubscribedEvents()[KernelEvents::VIEW][0][1]);
    }

    public function testStatusGetsUpdated(): void
    {
        $entity = new Quote();

        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('apply')
            ->once()
            ->with($entity, Graph::TRANSITION_NEW);

        $listener = new QuoteCreateListener($stateMachine);
        $request = Request::create('/', Request::METHOD_POST);
        $listener->setQuoteStatus(new ViewEvent(M::mock(KernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, $entity));
    }

    public function testSkipIfNotMainRequest(): void
    {
        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('apply')
            ->never();

        $listener = new QuoteCreateListener($stateMachine);
        $request = Request::create('/', Request::METHOD_POST);
        $listener->setQuoteStatus(new ViewEvent(M::mock(KernelInterface::class), $request, HttpKernelInterface::SUB_REQUEST, new Quote()));
    }

    public function testSkipIfQuoteAlreadyHasAStatus(): void
    {
        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('apply')
            ->never();

        $listener = new QuoteCreateListener($stateMachine);
        $request = Request::create('/', Request::METHOD_POST);
        $entity = new Quote();
        $entity->setStatus(Graph::STATUS_DRAFT);

        $listener->setQuoteStatus(new ViewEvent(M::mock(KernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, $entity));
    }

    public function testSkipIfNoQuoteIsPassed(): void
    {
        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('apply')
            ->never();

        $listener = new QuoteCreateListener($stateMachine);
        $request = Request::create('/', Request::METHOD_POST);

        $listener->setQuoteStatus(new ViewEvent(M::mock(KernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, new Invoice()));
    }

    public function testSkipIfNotPostRequest(): void
    {
        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('apply')
            ->never();

        $listener = new QuoteCreateListener($stateMachine);
        $request = Request::create('/', Request::METHOD_GET);

        $listener->setQuoteStatus(new ViewEvent(M::mock(KernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, new Quote()));
    }
}
