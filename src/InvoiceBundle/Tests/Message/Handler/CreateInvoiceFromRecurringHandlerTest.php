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

namespace SolidInvoice\InvoiceBundle\Tests\Message\Handler;

use PHPUnit\Framework\TestCase;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\InvoiceBundle\Message\CreateInvoiceFromRecurring;
use SolidInvoice\InvoiceBundle\Message\Handler\CreateInvoiceFromRecurringHandler;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Component\Workflow\StateMachine;
use function iterator_to_array;

/** @covers \SolidInvoice\InvoiceBundle\Message\Handler\CreateInvoiceFromRecurringHandler */
final class CreateInvoiceFromRecurringHandlerTest extends TestCase
{
    public function testGetHandledMessages(): void
    {
        self::assertSame(
            [
                CreateInvoiceFromRecurring::class => ['from_transport' => 'sync'],
            ],
            iterator_to_array(CreateInvoiceFromRecurringHandler::getHandledMessages())
        );
    }

    public function testHandler(): void
    {
        $recurringInvoice = new RecurringInvoice();
        $invoice = new Invoice();

        $invoiceManager = $this->createMock(InvoiceManager::class);
        $invoiceStateMachine = $this->createMock(StateMachine::class);

        $invoiceManager->expects(self::once())
            ->method('createFromRecurring')
            ->with($recurringInvoice)
            ->willReturn($invoice);

        $invoiceManager->expects(self::once())
            ->method('create')
            ->with($invoice);

        $invoiceStateMachine->expects(self::once())
            ->method('apply')
            ->with($invoice, Graph::TRANSITION_ACCEPT);

        $handler = new CreateInvoiceFromRecurringHandler($invoiceManager, $invoiceStateMachine);
        $handler(new CreateInvoiceFromRecurring(new RecurringInvoice()));
    }
}
