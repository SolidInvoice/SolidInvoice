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

use SolidInvoice\DashboardBundle\Stats\DashboardStats;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;

/**
 * @see \SolidInvoice\DashboardBundle\Tests\Widgets\AttentionRequiredWidgetTest
 */
final readonly class AttentionRequiredWidget implements WidgetInterface
{
    /**
     * How many rows each section renders before it is truncated.
     */
    private const int SECTION_LIMIT = 5;

    private const int UPCOMING_RECURRING_LIMIT = 3;

    private const int UPCOMING_RECURRING_DAYS = 7;

    public function __construct(
        private DashboardStats $stats,
        private RecurringInvoiceRepository $recurringRepository,
    ) {
    }

    /**
     * Each section returns a capped list plus the uncapped total, so the template
     * can say "5 of 12" instead of rendering the capped count as if it were the
     * whole truth. The totals are COUNT queries, so no extra rows are hydrated.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $overdueInvoices = $this->stats->overdueInvoices(self::SECTION_LIMIT);
        $draftInvoices = $this->stats->draftInvoices(self::SECTION_LIMIT);
        $pendingQuotes = $this->stats->pendingQuotes(self::SECTION_LIMIT);
        $upcomingRecurring = $this->recurringRepository->getUpcomingRecurringInvoices(
            self::UPCOMING_RECURRING_DAYS,
            self::UPCOMING_RECURRING_LIMIT
        );

        return [
            'overdueInvoices' => $overdueInvoices,
            'overdueInvoicesTotal' => $this->stats->invoiceCount(InvoiceStatus::Overdue),
            'draftInvoices' => $draftInvoices,
            'draftInvoicesTotal' => $this->stats->invoiceCount(InvoiceStatus::Draft),
            'pendingQuotes' => $pendingQuotes,
            'pendingQuotesTotal' => $this->stats->quoteCount(QuoteStatus::Pending),
            'upcomingRecurring' => $upcomingRecurring,
            // No repository method counts upcoming recurring invoices using the
            // same window as getUpcomingRecurringInvoices(), and the existing
            // getUpcomingCount() both hydrates every active recurring invoice and
            // counts a different thing (actual next run date). Null means "total
            // unknown" so the template omits the count rather than inventing one.
            'upcomingRecurringTotal' => null,
            'hasItems' => [] !== $overdueInvoices || [] !== $draftInvoices || [] !== $pendingQuotes || [] !== $upcomingRecurring,
        ];
    }

    public function getTemplate(): string
    {
        return '@SolidInvoiceDashboard/Widget/attention_required.html.twig';
    }
}
