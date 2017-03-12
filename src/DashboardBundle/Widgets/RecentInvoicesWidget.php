<?php

declare(strict_types=1);
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DashboardBundle\Widgets;

use CSBill\InvoiceBundle\Repository\InvoiceRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class RecentInvoicesWidget implements WidgetInterface
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
     * @return array
     */
    public function getData()
    {
        /** @var InvoiceRepository $invoiceRepository */
        $invoiceRepository = $this->manager->getRepository('CSBillInvoiceBundle:Invoice');

        $invoices = $invoiceRepository->getRecentInvoices();

        return ['invoices' => $invoices];
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return 'CSBillDashboardBundle:Widget:recent_invoices.html.twig';
    }
}
