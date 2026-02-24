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

use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Model\Graph as InvoiceGraph;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Model\Graph as QuoteGraph;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $config): void {
    $workflow = $config
        ->workflows()
        ->enabled(true);

    $invoiceWorkflow = $workflow
        ->workflows('invoice')
        ->type('state_machine')
        ->supports([
            Invoice::class,
        ])
        ->place(InvoiceStatus::New->value)
        ->place(InvoiceStatus::Draft->value)
        ->place(InvoiceStatus::Pending->value)
        ->place(InvoiceStatus::Active->value)
        ->place(InvoiceStatus::Overdue->value)
        ->place(InvoiceStatus::Cancelled->value)
        ->place(InvoiceStatus::Archived->value)
        ->place(InvoiceStatus::Paid->value);

    $invoiceWorkflow
        ->markingStore()
        ->type('method')
        ->property('statusValue');

    $invoiceWorkflow
        ->auditTrail()
        ->enabled(true);

    $invoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_NEW)
        ->from([InvoiceStatus::New->value])
        ->to([InvoiceStatus::Draft->value]);

    $invoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_ACCEPT)
        ->from([
            InvoiceStatus::New->value,
            InvoiceStatus::Draft->value,
        ])
        ->to([InvoiceStatus::Pending->value]);

    $invoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_CANCEL)
        ->from([
            InvoiceStatus::Draft->value,
            InvoiceStatus::Pending->value,
            InvoiceStatus::Overdue->value,
        ])
        ->to([InvoiceStatus::Cancelled->value]);

    $invoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_OVERDUE)
        ->from([InvoiceStatus::Pending->value])
        ->to([InvoiceStatus::Overdue->value]);

    $invoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_PAY)
        ->from([
            InvoiceStatus::Pending->value,
            InvoiceStatus::Overdue->value,
        ])
        ->to([InvoiceStatus::Paid->value]);

    $invoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_REOPEN)
        ->from([InvoiceStatus::Cancelled->value])
        ->to([InvoiceStatus::Draft->value]);

    $invoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_ARCHIVE)
        ->from([
            InvoiceStatus::New->value,
            InvoiceStatus::Draft->value,
            InvoiceStatus::Cancelled->value,
            InvoiceStatus::Paid->value,
        ])
        ->to([InvoiceStatus::Archived->value]);

    $invoiceWorkflow
        ->transition()
        ->name('edit')
        ->from([
            InvoiceStatus::Cancelled->value,
            InvoiceStatus::Draft->value,
            InvoiceStatus::Pending->value,
            InvoiceStatus::Overdue->value,
        ])
        ->to([InvoiceStatus::Draft->value]);

    $recurringInvoiceWorkflow = $workflow
        ->workflows('recurring_invoice')
        ->type('state_machine')
        ->supports([
            RecurringInvoice::class,
        ])
        ->place(RecurringInvoiceStatus::New->value)
        ->place(RecurringInvoiceStatus::Draft->value)
        ->place(RecurringInvoiceStatus::Active->value)
        ->place(RecurringInvoiceStatus::Paused->value)
        ->place(RecurringInvoiceStatus::Complete->value)
        ->place(RecurringInvoiceStatus::Cancelled->value)
        ->place(RecurringInvoiceStatus::Archived->value);

    $recurringInvoiceWorkflow
        ->markingStore()
        ->type('method')
        ->property('statusValue');

    $recurringInvoiceWorkflow
        ->auditTrail()
        ->enabled(true);

    $recurringInvoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_NEW)
        ->from([RecurringInvoiceStatus::New->value])
        ->to([RecurringInvoiceStatus::Draft->value]);

    $recurringInvoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_ACTIVATE)
        ->from([
            RecurringInvoiceStatus::New->value,
            RecurringInvoiceStatus::Draft->value,
        ])
        ->to([RecurringInvoiceStatus::Active->value]);

    $recurringInvoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_CANCEL)
        ->from([
            RecurringInvoiceStatus::Draft->value,
            RecurringInvoiceStatus::Active->value,
        ])
        ->to([RecurringInvoiceStatus::Cancelled->value]);

    $recurringInvoiceWorkflow
        ->transition()
        ->name('complete')
        ->from([RecurringInvoiceStatus::Active->value])
        ->to([RecurringInvoiceStatus::Complete->value]);

    $recurringInvoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_ARCHIVE)
        ->from([
            RecurringInvoiceStatus::New->value,
            RecurringInvoiceStatus::Draft->value,
            RecurringInvoiceStatus::Cancelled->value,
            RecurringInvoiceStatus::Active->value,
            RecurringInvoiceStatus::Paused->value,
        ])
        ->to([RecurringInvoiceStatus::Archived->value]);

    $recurringInvoiceWorkflow
        ->transition()
        ->name('edit')
        ->from([
            RecurringInvoiceStatus::Cancelled->value,
            RecurringInvoiceStatus::Draft->value,
            RecurringInvoiceStatus::Active->value,
            RecurringInvoiceStatus::Paused->value,
        ])
        ->to([RecurringInvoiceStatus::Draft->value]);

    $recurringInvoiceWorkflow
        ->transition()
        ->name('pause')
        ->from([RecurringInvoiceStatus::Active->value])
        ->to([RecurringInvoiceStatus::Paused->value]);

    $recurringInvoiceWorkflow
        ->transition()
        ->name('resume')
        ->from([RecurringInvoiceStatus::Paused->value])
        ->to([RecurringInvoiceStatus::Active->value]);

    $quoteWorkflow = $workflow
        ->workflows('quote')
        ->type('state_machine')
        ->supports([
            Quote::class,
        ])
        ->place(QuoteStatus::New->value)
        ->place(QuoteStatus::Draft->value)
        ->place(QuoteStatus::Pending->value)
        ->place(QuoteStatus::Cancelled->value)
        ->place(QuoteStatus::Archived->value)
        ->place(QuoteStatus::Accepted->value)
        ->place(QuoteStatus::Declined->value);

    $quoteWorkflow
        ->auditTrail()
        ->enabled(true);

    $quoteWorkflow
        ->markingStore()
        ->type('method')
        ->property('statusValue');

    $quoteWorkflow
        ->transition()
        ->name(QuoteGraph::TRANSITION_NEW)
        ->from([
            QuoteStatus::New->value,
            QuoteStatus::Cancelled->value,
        ])
        ->to([QuoteStatus::Draft->value]);

    $quoteWorkflow
        ->transition()
        ->name(QuoteGraph::TRANSITION_SEND)
        ->from([
            QuoteStatus::New->value,
            QuoteStatus::Draft->value,
        ])
        ->to([QuoteStatus::Pending->value]);

    $quoteWorkflow
        ->transition()
        ->name(QuoteGraph::TRANSITION_PUBLISH)
        ->from([
            QuoteStatus::New->value,
            QuoteStatus::Draft->value,
        ])
        ->to([QuoteStatus::Pending->value]);

    $quoteWorkflow
        ->transition()
        ->name(QuoteGraph::TRANSITION_CANCEL)
        ->from([
            QuoteStatus::Draft->value,
            QuoteStatus::Pending->value,
        ])
        ->to([QuoteStatus::Cancelled->value]);

    $quoteWorkflow
        ->transition()
        ->name(QuoteGraph::TRANSITION_DECLINE)
        ->from([
            QuoteStatus::New->value,
            QuoteStatus::Draft->value,
            QuoteStatus::Pending->value,
        ])
        ->to([QuoteStatus::Declined->value]);

    $quoteWorkflow
        ->transition()
        ->name(QuoteGraph::TRANSITION_ACCEPT)
        ->from([
            QuoteStatus::Pending->value,
        ])
        ->to([QuoteStatus::Accepted->value]);

    $quoteWorkflow
        ->transition()
        ->name(QuoteGraph::TRANSITION_REOPEN)
        ->from([
            QuoteStatus::Declined->value,
            QuoteStatus::Cancelled->value,
        ])
        ->to([QuoteStatus::Draft->value]);

    $quoteWorkflow
        ->transition()
        ->name(QuoteGraph::TRANSITION_ARCHIVE)
        ->from([
            QuoteStatus::New->value,
            QuoteStatus::Draft->value,
            QuoteStatus::Cancelled->value,
            QuoteStatus::Accepted->value,
            QuoteStatus::Declined->value,
            QuoteStatus::Pending->value,
        ])
        ->to([QuoteStatus::Archived->value]);
};
