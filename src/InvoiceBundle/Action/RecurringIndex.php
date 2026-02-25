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

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;

final readonly class RecurringIndex
{
    public function __construct(
        private RecurringInvoiceRepository $repository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{recurring: bool, isArchived: bool, isCompleted: bool, totalActiveRecurring: int, totalArchivedRecurring: int, activeCount: int, draftCount: int, pausedCount: int, cancelledCount: int, completeCount: int, upcomingIn7Days: int, totalGeneratedInvoices: int, monthlyRecurringRevenue: array<string, mixed>, status_list_count: array{active: int, draft: int, paused: int, cancelled: int, complete: int}}
     */
    #[Template('@SolidInvoiceInvoice/Default/index.html.twig')]
    public function __invoke(Request $request): array
    {
        $isArchived = $request->query->get('archived', '0') === '1';
        $isCompleted = $request->query->get('completed', '0') === '1';

        // Get recurring invoice counts by status
        $activeCount = $this->repository->getCountByStatus(RecurringInvoiceStatus::Active);
        $draftCount = $this->repository->getCountByStatus(RecurringInvoiceStatus::Draft);
        $pausedCount = $this->repository->getCountByStatus(RecurringInvoiceStatus::Paused);
        $cancelledCount = $this->repository->getCountByStatus(RecurringInvoiceStatus::Cancelled);
        $completeCount = $this->repository->getCountByStatus(RecurringInvoiceStatus::Complete);

        // Calculate total active recurring invoices (non-archived, non-cancelled, non-complete)
        $totalActiveRecurring = $activeCount + $draftCount + $pausedCount;

        // Get archived recurring invoices count
        $filters = $this->entityManager->getFilters();
        $filters->disable('archivable');
        try {
            $totalArchivedRecurring = $this->repository->count(['archived' => true]);
        } finally {
            $filters->enable('archivable');
        }

        // Get upcoming recurring invoices count (next 7 days)
        $upcomingIn7Days = $this->repository->getUpcomingCount(7);

        // Get total generated invoices from recurring invoices
        $totalGeneratedInvoices = $this->repository->getTotalGeneratedInvoices();

        // Get Monthly Recurring Revenue by currency
        $monthlyRecurringRevenue = $this->repository->getMonthlyRecurringRevenueByCurrency();

        return [
            'recurring' => true,
            'isArchived' => $isArchived,
            'isCompleted' => $isCompleted,
            'totalActiveRecurring' => $totalActiveRecurring,
            'totalArchivedRecurring' => $totalArchivedRecurring,
            'activeCount' => $activeCount,
            'draftCount' => $draftCount,
            'pausedCount' => $pausedCount,
            'cancelledCount' => $cancelledCount,
            'completeCount' => $completeCount,
            'upcomingIn7Days' => $upcomingIn7Days,
            'totalGeneratedInvoices' => $totalGeneratedInvoices,
            'monthlyRecurringRevenue' => $monthlyRecurringRevenue,
            'status_list_count' => [
                RecurringInvoiceStatus::Active->value => $activeCount,
                RecurringInvoiceStatus::Draft->value => $draftCount,
                RecurringInvoiceStatus::Paused->value => $pausedCount,
                RecurringInvoiceStatus::Cancelled->value => $cancelledCount,
                RecurringInvoiceStatus::Complete->value => $completeCount,
            ],
        ];
    }
}
