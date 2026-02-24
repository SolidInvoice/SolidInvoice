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
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;

final readonly class HeroStatsWidget implements WidgetInterface
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
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->manager->getRepository(Payment::class);

        return [
            'totalOutstanding' => $invoiceRepository->getTotalOutstandingByCurrency(),
            'overdueCount' => $invoiceRepository->getCountByStatus(InvoiceStatus::Overdue),
            'overdueAmount' => $invoiceRepository->getOverdueAmountByCurrency(),
            'paymentsThisMonth' => $paymentRepository->getPaymentsThisMonth(),
            'totalRevenue' => $paymentRepository->getTotalIncome(),
        ];
    }

    public function getTemplate(): string
    {
        return '@SolidInvoiceDashboard/Widget/hero_stats.html.twig';
    }
}
