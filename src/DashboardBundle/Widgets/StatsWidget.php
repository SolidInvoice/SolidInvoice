<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DashboardBundle\Widgets;

use Doctrine\Common\Persistence\ManagerRegistry;
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
    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    private $manager;

    public function __construct(ManagerRegistry $registry)
    {
        $this->manager = $registry->getManager();
    }

    /**
     * {@inheritdoc}
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
            'totalIncome' => $paymentRepository->getTotalIncome(null, true),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate(): string
    {
        return '@SolidInvoiceDashboard/Widget/stats.html.twig';
    }
}
