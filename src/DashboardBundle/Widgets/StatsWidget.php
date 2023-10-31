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
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Model\Status as ClientStatus;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Model\Graph as InvoiceGraph;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Model\Graph as QuoteGraph;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;

class StatsWidget implements WidgetInterface
{
    private readonly ObjectManager $manager;

    public function __construct(ManagerRegistry $registry)
    {
        $this->manager = $registry->getManager();
    }

    /**
     * @return array<string, int>
     */
    public function getData(): array
    {
        /** @var ClientRepository $clientRepository */
        $clientRepository = $this->manager->getRepository(Client::class);
        /** @var QuoteRepository $quoteRepository */
        $quoteRepository = $this->manager->getRepository(Quote::class);
        /** @var InvoiceRepository $invoiceRepository */
        $invoiceRepository = $this->manager->getRepository(Invoice::class);
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->manager->getRepository(Payment::class);

        $totalInvoices = $invoiceRepository->getCountByStatus(
            [
                InvoiceGraph::STATUS_PENDING,
                InvoiceGraph::STATUS_OVERDUE,
            ]
        );

        return [
            'totalClients' => $clientRepository->getTotalClients(ClientStatus::STATUS_ACTIVE),
            'totalQuotes' => $quoteRepository->getTotalQuotes(QuoteGraph::STATUS_PENDING),
            'totalInvoices' => $totalInvoices,
            'totalIncome' => $paymentRepository->getTotalIncome(),
        ];
    }

    public function getTemplate(): string
    {
        return '@SolidInvoiceDashboard/Widget/stats.html.twig';
    }
}
