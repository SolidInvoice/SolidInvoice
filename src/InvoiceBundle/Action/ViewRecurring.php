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

namespace SolidInvoice\InvoiceBundle\Action;

use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Recurring\RecurringSchedule;

final readonly class ViewRecurring
{
    public function __construct(
        private RecurringSchedule $recurringSchedule
    ) {
    }

    public function __invoke(RecurringInvoice $invoice): Template
    {
        // Get next 5 upcoming occurrences for active invoices
        $nextOccurrences = [];
        if ($invoice->getStatus() === 'active') {
            $nextOccurrences = iterator_to_array(
                $this->recurringSchedule->getNextOccurrences($invoice->getRecurringOptions(), 5)
            );
        }

        // Get last 5 generated invoices (collection is ordered by created DESC via OrderBy annotation)
        $invoicesCollection = $invoice->getInvoices();
        $totalGenerated = $invoicesCollection->count();
        $generatedInvoices = $invoicesCollection->slice(0, 5);

        return new Template('@SolidInvoiceInvoice/Default/view_recurring.html.twig', [
            'invoice' => $invoice,
            'nextOccurrences' => $nextOccurrences,
            'generatedInvoices' => $generatedInvoices,
            'totalGenerated' => $totalGenerated,
        ]);
    }
}
