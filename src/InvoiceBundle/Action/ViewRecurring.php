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

use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Recurring\RecurringSchedule;
use Symfony\Bridge\Twig\Attribute\Template;

final readonly class ViewRecurring
{
    public function __construct(
        private RecurringSchedule $recurringSchedule
    ) {
    }

    /**
     * @return array{invoice: RecurringInvoice, nextOccurrences: array<\Carbon\CarbonInterface>, generatedInvoices: array<int, \SolidInvoice\InvoiceBundle\Entity\Invoice>, totalGenerated: int}
     */
    #[Template('@SolidInvoiceInvoice/Default/view_recurring.html.twig')]
    public function __invoke(RecurringInvoice $invoice): array
    {
        // Get next 5 upcoming occurrences for active invoices
        $nextOccurrences = [];
        if ($invoice->getStatus() === RecurringInvoiceStatus::Active->value) {
            $nextOccurrences = iterator_to_array(
                $this->recurringSchedule->getNextOccurrences($invoice->getRecurringOptions(), 5)
            );
        }

        // Get last 5 generated invoices (collection is ordered by created DESC via OrderBy annotation)
        $invoicesCollection = $invoice->getInvoices();
        $totalGenerated = $invoicesCollection->count();
        $generatedInvoices = $invoicesCollection->slice(0, 5);

        return [
            'invoice' => $invoice,
            'nextOccurrences' => $nextOccurrences,
            'generatedInvoices' => $generatedInvoices,
            'totalGenerated' => $totalGenerated,
        ];
    }
}
