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
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;

final readonly class Index
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private PaymentRepository $paymentRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    #[Template('@SolidInvoiceInvoice/Default/index.html.twig')]
    public function __invoke(Request $request): array
    {
        $isArchived = $request->query->get('archived', '0') === '1';

        // Get invoice counts by status
        $pendingCount = $this->invoiceRepository->getCountByStatus(Graph::STATUS_PENDING);
        $paidCount = $this->invoiceRepository->getCountByStatus(Graph::STATUS_PAID);
        $cancelledCount = $this->invoiceRepository->getCountByStatus(Graph::STATUS_CANCELLED);
        $draftCount = $this->invoiceRepository->getCountByStatus(Graph::STATUS_DRAFT);
        $overdueCount = $this->invoiceRepository->getCountByStatus(Graph::STATUS_OVERDUE);

        // Calculate total active invoices
        $totalActiveInvoices = $pendingCount + $paidCount + $cancelledCount + $draftCount + $overdueCount;

        // Get archived invoices count (need to temporarily disable the filter)
        $filters = $this->entityManager->getFilters();
        $filters->disable('archivable');
        try {
            $totalArchivedInvoices = $this->invoiceRepository->count(['archived' => true]);
        } finally {
            $filters->enable('archivable');
        }

        return [
            'recurring' => false,
            'isArchived' => $isArchived,
            'totalActiveInvoices' => $totalActiveInvoices,
            'totalArchivedInvoices' => $totalArchivedInvoices,
            'pendingCount' => $pendingCount,
            'paidCount' => $paidCount,
            'overdueCount' => $overdueCount,
            'draftCount' => $draftCount,
            'status_list_count' => [
                Graph::STATUS_PENDING => $pendingCount,
                Graph::STATUS_PAID => $paidCount,
                Graph::STATUS_CANCELLED => $cancelledCount,
                Graph::STATUS_DRAFT => $draftCount,
                Graph::STATUS_OVERDUE => $overdueCount,
            ],
            'total_income' => $this->paymentRepository->getTotalIncome(),
            'total_outstanding' => $this->invoiceRepository->getTotalOutstandingByCurrency(),
        ];
    }
}
