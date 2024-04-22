<?php

declare(strict_types=1);

use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Model\Graph as InvoiceGraph;
use SolidInvoice\QuoteBundle\Entity\Quote;
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
        ->place(InvoiceGraph::STATUS_NEW)
        ->place(InvoiceGraph::STATUS_DRAFT)
        ->place(InvoiceGraph::STATUS_PENDING)
        ->place(InvoiceGraph::STATUS_ACTIVE)
        ->place(InvoiceGraph::STATUS_OVERDUE)
        ->place(InvoiceGraph::STATUS_CANCELLED)
        ->place(InvoiceGraph::STATUS_ARCHIVED)
        ->place(InvoiceGraph::STATUS_PAID);

    $invoiceWorkflow
        ->markingStore()
        ->type('method')
        ->property('status');

    $invoiceWorkflow
        ->auditTrail()
        ->enabled(true);

    $invoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_NEW)
        ->from([InvoiceGraph::STATUS_NEW])
        ->to([InvoiceGraph::STATUS_DRAFT]);

    $invoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_ACCEPT)
        ->from([
            InvoiceGraph::STATUS_NEW,
            InvoiceGraph::STATUS_DRAFT,
        ])
        ->to([InvoiceGraph::STATUS_PENDING]);

    $invoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_CANCEL)
        ->from([
            InvoiceGraph::STATUS_DRAFT,
            InvoiceGraph::STATUS_PENDING,
            InvoiceGraph::STATUS_OVERDUE,
        ])
        ->to([InvoiceGraph::STATUS_CANCELLED]);

    $invoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_OVERDUE)
        ->from([InvoiceGraph::STATUS_PENDING])
        ->to([InvoiceGraph::STATUS_OVERDUE]);

    $invoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_PAY)
        ->from([
            InvoiceGraph::STATUS_PENDING,
            InvoiceGraph::STATUS_OVERDUE,
        ])
        ->to([InvoiceGraph::STATUS_PAID]);

    $invoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_REOPEN)
        ->from([InvoiceGraph::STATUS_CANCELLED])
        ->to([InvoiceGraph::STATUS_DRAFT]);

    $invoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_ARCHIVE)
        ->from([
            InvoiceGraph::STATUS_NEW,
            InvoiceGraph::STATUS_DRAFT,
            InvoiceGraph::STATUS_CANCELLED,
            InvoiceGraph::STATUS_PAID,
        ])
        ->to([InvoiceGraph::STATUS_ARCHIVED]);

    $invoiceWorkflow
        ->transition()
        ->name('edit')
        ->from([
            InvoiceGraph::STATUS_CANCELLED,
            InvoiceGraph::STATUS_DRAFT,
            InvoiceGraph::STATUS_PENDING,
            InvoiceGraph::STATUS_OVERDUE,
        ])
        ->to([InvoiceGraph::STATUS_DRAFT]);

    $recurringInvoiceWorkflow = $workflow
        ->workflows('recurring_invoice')
        ->type('state_machine')
        ->supports([
            RecurringInvoice::class,
        ])
        ->place(InvoiceGraph::STATUS_NEW)
        ->place(InvoiceGraph::STATUS_DRAFT)
        ->place(InvoiceGraph::STATUS_ACTIVE)
        ->place('paused')
        ->place('complete')
        ->place(InvoiceGraph::STATUS_CANCELLED)
        ->place(InvoiceGraph::STATUS_ARCHIVED);

    $recurringInvoiceWorkflow
        ->markingStore()
        ->type('method')
        ->property('status');

    $recurringInvoiceWorkflow
        ->auditTrail()
        ->enabled(true);

    $recurringInvoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_NEW)
        ->from([InvoiceGraph::STATUS_NEW])
        ->to([InvoiceGraph::STATUS_DRAFT]);

    $recurringInvoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_ACTIVATE)
        ->from([
            InvoiceGraph::STATUS_NEW,
            InvoiceGraph::STATUS_DRAFT,
        ])
        ->to([InvoiceGraph::STATUS_ACTIVE]);

    $recurringInvoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_CANCEL)
        ->from([
            InvoiceGraph::STATUS_DRAFT,
            InvoiceGraph::STATUS_ACTIVE,
        ])
        ->to([InvoiceGraph::STATUS_CANCELLED]);

    $recurringInvoiceWorkflow
        ->transition()
        ->name('complete')
        ->from([InvoiceGraph::STATUS_ACTIVE])
        ->to(['complete']);

    $recurringInvoiceWorkflow
        ->transition()
        ->name(InvoiceGraph::TRANSITION_ARCHIVE)
        ->from([
            InvoiceGraph::STATUS_NEW,
            InvoiceGraph::STATUS_DRAFT,
            InvoiceGraph::STATUS_CANCELLED,
            InvoiceGraph::STATUS_ACTIVE,
            'paused',
        ])
        ->to([InvoiceGraph::STATUS_ARCHIVED]);

    $recurringInvoiceWorkflow
        ->transition()
        ->name('edit')
        ->from([
            InvoiceGraph::STATUS_CANCELLED,
            InvoiceGraph::STATUS_DRAFT,
            InvoiceGraph::STATUS_ACTIVE,
            'paused',
        ])
        ->to([InvoiceGraph::STATUS_DRAFT]);


    $quoteWorkflow = $workflow
        ->workflows('quote')
        ->type('state_machine')
        ->supports([
            Quote::class,
        ])
        ->place(QuoteGraph::STATUS_NEW)
        ->place(QuoteGraph::STATUS_DRAFT)
        ->place(QuoteGraph::STATUS_PENDING)
        ->place(QuoteGraph::STATUS_CANCELLED)
        ->place(QuoteGraph::STATUS_ARCHIVED)
        ->place(QuoteGraph::STATUS_ACCEPTED)
        ->place(QuoteGraph::STATUS_DECLINED);

    $quoteWorkflow
        ->auditTrail()
        ->enabled(true);

    $quoteWorkflow
        ->markingStore()
        ->type('method')
        ->property('status');

    $quoteWorkflow
        ->transition()
        ->name(QuoteGraph::TRANSITION_NEW)
        ->from([
            QuoteGraph::STATUS_NEW,
            QuoteGraph::STATUS_CANCELLED,
        ])
        ->to([QuoteGraph::STATUS_DRAFT]);

    $quoteWorkflow
        ->transition()
        ->name(QuoteGraph::TRANSITION_SEND)
        ->from([
            QuoteGraph::STATUS_NEW,
            QuoteGraph::STATUS_DRAFT,
        ])
        ->to([QuoteGraph::STATUS_PENDING]);

    $quoteWorkflow
        ->transition()
        ->name(QuoteGraph::TRANSITION_CANCEL)
        ->from([
            QuoteGraph::STATUS_DRAFT,
            QuoteGraph::STATUS_PENDING,
        ])
        ->to([QuoteGraph::STATUS_CANCELLED]);

    $quoteWorkflow
        ->transition()
        ->name(QuoteGraph::TRANSITION_DECLINE)
        ->from([
            QuoteGraph::STATUS_NEW,
            QuoteGraph::STATUS_DRAFT,
            QuoteGraph::STATUS_PENDING,
        ])
        ->to([QuoteGraph::STATUS_DECLINED]);

    $quoteWorkflow
        ->transition()
        ->name(QuoteGraph::TRANSITION_ACCEPT)
        ->from([
            QuoteGraph::STATUS_PENDING,
        ])
        ->to([QuoteGraph::STATUS_ACCEPTED]);

    $quoteWorkflow
        ->transition()
        ->name(QuoteGraph::TRANSITION_REOPEN)
        ->from([
            QuoteGraph::STATUS_DECLINED,
            QuoteGraph::STATUS_CANCELLED,
        ])
        ->to([QuoteGraph::STATUS_DRAFT]);

    $quoteWorkflow
        ->transition()
        ->name(QuoteGraph::TRANSITION_ARCHIVE)
        ->from([
            QuoteGraph::STATUS_NEW,
            QuoteGraph::STATUS_DRAFT,
            QuoteGraph::STATUS_CANCELLED,
            QuoteGraph::STATUS_ACCEPTED,
            QuoteGraph::STATUS_DECLINED,
            QuoteGraph::STATUS_PENDING,
        ])
        ->to([QuoteGraph::STATUS_ARCHIVED]);
};
