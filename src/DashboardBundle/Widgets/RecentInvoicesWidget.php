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

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;

class RecentInvoicesWidget implements WidgetInterface
{
    /**
     * @var \Doctrine\Persistence\ObjectManager
     */
    private $manager;

    public function __construct(ManagerRegistry $registry)
    {
        $this->manager = $registry->getManager();
    }

    public function getData(): array
    {
        /** @var InvoiceRepository $invoiceRepository */
        $invoiceRepository = $this->manager->getRepository(Invoice::class);

        $invoices = $invoiceRepository->getRecentInvoices();

        return ['invoices' => $invoices];
    }

    public function getTemplate(): string
    {
        return '@SolidInvoiceDashboard/Widget/recent_invoices.html.twig';
    }
}
