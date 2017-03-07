<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DashboardBundle\Widgets;

use CSBill\ClientBundle\Model\Status as ClientStatus;
use CSBill\ClientBundle\Repository\ClientRepository;
use CSBill\InvoiceBundle\Model\Graph as InvoiceGraph;
use CSBill\InvoiceBundle\Repository\InvoiceRepository;
use CSBill\PaymentBundle\Repository\PaymentRepository;
use CSBill\QuoteBundle\Model\Graph as QuoteGraph;
use CSBill\QuoteBundle\Repository\QuoteRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class StatsWidget implements WidgetInterface
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    private $manager;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->manager = $registry->getManager();
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        /** @var ClientRepository $clientRepository */
        $clientRepository = $this->manager->getRepository('CSBillClientBundle:Client');
        /** @var QuoteRepository $quoteRepository */
        $quoteRepository = $this->manager->getRepository('CSBillQuoteBundle:Quote');
        /** @var InvoiceRepository $invoiceRepository */
        $invoiceRepository = $this->manager->getRepository('CSBillInvoiceBundle:Invoice');
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->manager->getRepository('CSBillPaymentBundle:Payment');

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
    public function getTemplate()
    {
        return 'CSBillDashboardBundle:Widget:stats.html.twig';
    }
}
