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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Exception\InvalidTransitionException;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Service\InvoiceStatusTransitionService;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;

/** @covers \SolidInvoice\InvoiceBundle\Service\InvoiceStatusTransitionService */
final class InvoiceStatusTransitionServiceTest extends TestCase
{
    use DoctrineTestTrait;

    public function testApplyTransition(): void
    {
        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne()->_real());
        $invoice->setStatus(Graph::STATUS_PENDING);

        /** @var StateMachine&MockObject $stateMachine */
        $stateMachine = $this->createMock(StateMachine::class);
        $stateMachine->expects($this->once())
            ->method('can')
            ->with($invoice, Graph::TRANSITION_OVERDUE)
            ->willReturn(true);

        $stateMachine->expects($this->once())
            ->method('apply')
            ->with($invoice, Graph::TRANSITION_OVERDUE);

        $service = new InvoiceStatusTransitionService($stateMachine, $this->registry);
        $service->applyTransition($invoice, Graph::TRANSITION_OVERDUE);

        // Verify invoice was persisted
        self::assertSame($invoice, $this->em->getRepository(Invoice::class)->find($invoice->getId()));
    }

    public function testApplyTransitionThrowsExceptionWhenTransitionNotAllowed(): void
    {
        $invoice = new Invoice();
        $invoice->setStatus(Graph::STATUS_PAID);

        /** @var StateMachine&MockObject $stateMachine */
        $stateMachine = $this->createMock(StateMachine::class);
        $stateMachine->expects($this->once())
            ->method('can')
            ->with($invoice, Graph::TRANSITION_OVERDUE)
            ->willReturn(false);

        $service = new InvoiceStatusTransitionService($stateMachine, $this->registry);

        $this->expectException(InvalidTransitionException::class);
        $service->applyTransition($invoice, Graph::TRANSITION_OVERDUE);
    }

    public function testCanApplyTransition(): void
    {
        $invoice = new Invoice();
        $invoice->setStatus(Graph::STATUS_PENDING);

        /** @var StateMachine&MockObject $stateMachine */
        $stateMachine = $this->createMock(StateMachine::class);
        $stateMachine->expects($this->once())
            ->method('can')
            ->with($invoice, Graph::TRANSITION_OVERDUE)
            ->willReturn(true);

        $service = new InvoiceStatusTransitionService($stateMachine, $this->registry);

        self::assertTrue($service->canApplyTransition($invoice, Graph::TRANSITION_OVERDUE));
    }

    public function testGetAvailableTransitions(): void
    {
        $invoice = new Invoice();
        $invoice->setStatus(Graph::STATUS_PENDING);

        /** @var Transition&MockObject $transition1 */
        $transition1 = $this->createMock(Transition::class);
        $transition1->method('getName')->willReturn(Graph::TRANSITION_OVERDUE);

        /** @var Transition&MockObject $transition2 */
        $transition2 = $this->createMock(Transition::class);
        $transition2->method('getName')->willReturn(Graph::TRANSITION_PAY);

        /** @var StateMachine&MockObject $stateMachine */
        $stateMachine = $this->createMock(StateMachine::class);
        $stateMachine->expects($this->once())
            ->method('getEnabledTransitions')
            ->with($invoice)
            ->willReturn([$transition1, $transition2]);

        $service = new InvoiceStatusTransitionService($stateMachine, $this->registry);
        $transitions = $service->getAvailableTransitions($invoice);

        self::assertEquals([Graph::TRANSITION_OVERDUE, Graph::TRANSITION_PAY], $transitions);
    }
}
