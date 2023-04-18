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

namespace SolidInvoice\InvoiceBundle\Message\Handler;

use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\InvoiceBundle\Message\CreateInvoiceFromRecurring;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
use Symfony\Component\Workflow\StateMachine;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Message\Handler\CreateInvoiceFromRecurringHandlerTest
 */
final class CreateInvoiceFromRecurringHandler implements MessageSubscriberInterface
{
    private StateMachine $stateMachine;

    private InvoiceManager $invoiceManager;

    public function __construct(InvoiceManager $invoiceManager, StateMachine $invoiceStateMachine)
    {
        $this->stateMachine = $invoiceStateMachine;
        $this->invoiceManager = $invoiceManager;
    }

    /**
     * @return iterable<string, array<string, string>>
     */
    public static function getHandledMessages(): iterable
    {
        yield CreateInvoiceFromRecurring::class => [
            'from_transport' => 'sync',
        ];
    }

    public function __invoke(CreateInvoiceFromRecurring $message): void
    {
        $invoice = $message->getRecurringInvoice();
        $newInvoice = $this->invoiceManager->createFromRecurring($invoice);
        $this->invoiceManager->create($newInvoice);

        $this->stateMachine->apply($newInvoice, Graph::TRANSITION_ACCEPT);
    }
}
