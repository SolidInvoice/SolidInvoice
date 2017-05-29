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
use CSBill\InvoiceBundle\Cloner\InvoiceCloner;
use CSBill\InvoiceBundle\Entity\Invoice;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class CloneInvoice
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var InvoiceCloner
     */
    private $cloner;

    public function __construct(RouterInterface $router, InvoiceCloner $cloner)
    {
        $this->router = $router;
        $this->cloner = $cloner;
    }

    public function __invoke(Request $request, Invoice $invoice)
    {
        $newInvoice = $this->cloner->clone($invoice);

        $route = $this->router->generate('_invoices_view', ['id' => $newInvoice->getId()]);

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): iterable
            {
                yield FlashResponse::FLASH_SUCCESS => 'invoice.clone.success';
            }
        };
    }
}
