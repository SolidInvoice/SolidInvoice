<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Action;

use CSBill\CoreBundle\Response\FlashResponse;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Manager\InvoiceManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class CloneInvoice
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var InvoiceManager
     */
    private $invoiceManager;

    public function __construct(RouterInterface $router, InvoiceManager $invoiceManager)
    {
        $this->router = $router;
        $this->invoiceManager = $invoiceManager;
    }

    public function __invoke(Request $request, Invoice $invoice)
    {
        $newInvoice = $this->invoiceManager->duplicate($invoice);

        $route = $this->router->generate('_invoices_view', ['id' => $newInvoice->getId()]);

        return new class($route) extends RedirectResponse implements FlashResponse
        {
            public function getFlash(): iterable
            {
                yield FlashResponse::FLASH_SUCCESS => 'invoice.clone.success';
            }
        };
    }
}