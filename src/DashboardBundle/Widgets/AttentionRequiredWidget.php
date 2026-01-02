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

namespace SolidInvoice\DashboardBundle\Widgets;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;

final readonly class AttentionRequiredWidget implements WidgetInterface
{
    private ObjectManager $manager;

    public function __construct(ManagerRegistry $registry)
    {
        $this->manager = $registry->getManager();
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        /** @var InvoiceRepository $invoiceRepository */
        $invoiceRepository = $this->manager->getRepository(Invoice::class);
        /** @var QuoteRepository $quoteRepository */
        $quoteRepository = $this->manager->getRepository(Quote::class);
        /** @var RecurringInvoiceRepository $recurringRepository */
        $recurringRepository = $this->manager->getRepository(RecurringInvoice::class);

        $overdueInvoices = $invoiceRepository->getOverdueInvoices(5);
        $draftInvoices = $invoiceRepository->getDraftInvoices(5);
        $pendingQuotes = $quoteRepository->getPendingQuotes(5);
        $upcomingRecurring = $recurringRepository->getUpcomingRecurringInvoices(7, 3);

        return [
            'overdueInvoices' => $overdueInvoices,
            'draftInvoices' => $draftInvoices,
            'pendingQuotes' => $pendingQuotes,
            'upcomingRecurring' => $upcomingRecurring,
            'hasItems' => ! empty($overdueInvoices) || ! empty($draftInvoices) || ! empty($pendingQuotes) || ! empty($upcomingRecurring),
        ];
    }

    public function getTemplate(): string
    {
        return '@SolidInvoiceDashboard/Widget/attention_required.html.twig';
    }
}
