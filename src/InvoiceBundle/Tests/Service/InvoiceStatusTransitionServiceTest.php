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

namespace SolidInvoice\InvoiceBundle\Tests\Service;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Exception\InvalidTransitionException;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Service\InvoiceStatusTransitionService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\StateMachine;
use Zenstruck\Foundry\Test\Factories;

/** @covers \SolidInvoice\InvoiceBundle\Service\InvoiceStatusTransitionService */
final class InvoiceStatusTransitionServiceTest extends KernelTestCase
{
    use DoctrineTestTrait;
    use Factories;
    use MockeryPHPUnitIntegration;

    public function testApplyTransition(): void
    {
        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne()->_real());
        $invoice->setStatus(InvoiceStatus::Pending);

        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('can')
            ->once()
            ->with($invoice, Graph::TRANSITION_OVERDUE)
            ->andReturn(true);

        $stateMachine->shouldReceive('apply')
            ->once()
            ->with($invoice, Graph::TRANSITION_OVERDUE);

        $service = new InvoiceStatusTransitionService($stateMachine, $this->registry);
        $service->applyTransition($invoice, Graph::TRANSITION_OVERDUE);

        // Verify invoice was persisted
        self::assertSame($invoice, $this->em->getRepository(Invoice::class)->find($invoice->getId()));
    }

    public function testApplyTransitionThrowsExceptionWhenTransitionNotAllowed(): void
    {
        $invoice = new Invoice();
        $invoice->setStatus(InvoiceStatus::Paid);

        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('can')
            ->once()
            ->with($invoice, Graph::TRANSITION_OVERDUE)
            ->andReturn(false);

        $service = new InvoiceStatusTransitionService($stateMachine, $this->registry);

        $this->expectException(InvalidTransitionException::class);
        $service->applyTransition($invoice, Graph::TRANSITION_OVERDUE);
    }

    public function testCanApplyTransition(): void
    {
        $invoice = new Invoice();
        $invoice->setStatus(InvoiceStatus::Pending);

        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('can')
            ->once()
            ->with($invoice, Graph::TRANSITION_OVERDUE)
            ->andReturn(true);

        $service = new InvoiceStatusTransitionService($stateMachine, $this->registry);

        self::assertTrue($service->canApplyTransition($invoice, Graph::TRANSITION_OVERDUE));
    }

    public function testGetAvailableTransitions(): void
    {
        $invoice = new Invoice();
        $invoice->setStatus(InvoiceStatus::Pending);

        $transition1 = M::mock(\Symfony\Component\Workflow\Transition::class);
        $transition1->shouldReceive('getName')->andReturn(Graph::TRANSITION_OVERDUE);

        $transition2 = M::mock(\Symfony\Component\Workflow\Transition::class);
        $transition2->shouldReceive('getName')->andReturn(Graph::TRANSITION_PAY);

        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('getEnabledTransitions')
            ->once()
            ->with($invoice)
            ->andReturn([$transition1, $transition2]);

        $service = new InvoiceStatusTransitionService($stateMachine, $this->registry);
        $transitions = $service->getAvailableTransitions($invoice);

        self::assertEquals([Graph::TRANSITION_OVERDUE, Graph::TRANSITION_PAY], $transitions);
    }
}
