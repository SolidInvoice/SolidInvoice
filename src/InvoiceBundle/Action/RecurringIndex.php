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
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use Symfony\Component\HttpFoundation\Request;

final readonly class RecurringIndex
{
    public function __construct(
        private RecurringInvoiceRepository $repository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(Request $request): Template
    {
        $isArchived = $request->query->get('archived', '0') === '1';
        $isCompleted = $request->query->get('completed', '0') === '1';

        // Get recurring invoice counts by status
        $activeCount = $this->repository->getCountByStatus('active');
        $draftCount = $this->repository->getCountByStatus('draft');
        $pausedCount = $this->repository->getCountByStatus('paused');
        $cancelledCount = $this->repository->getCountByStatus('cancelled');
        $completeCount = $this->repository->getCountByStatus('complete');

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

        return new Template(
            '@SolidInvoiceInvoice/Default/index.html.twig',
            [
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
                    'active' => $activeCount,
                    'draft' => $draftCount,
                    'paused' => $pausedCount,
                    'cancelled' => $cancelledCount,
                    'complete' => $completeCount,
                ],
            ]
        );
    }
}
