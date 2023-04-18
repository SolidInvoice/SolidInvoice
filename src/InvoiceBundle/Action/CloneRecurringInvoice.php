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

use Generator;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\InvoiceBundle\Cloner\InvoiceCloner;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class CloneRecurringInvoice
{
    private RouterInterface $router;

    private InvoiceCloner $cloner;

    public function __construct(RouterInterface $router, InvoiceCloner $cloner)
    {
        $this->router = $router;
        $this->cloner = $cloner;
    }

    public function __invoke(Request $request, RecurringInvoice $invoice)
    {
        $newInvoice = $this->cloner->clone($invoice);

        $route = $this->router->generate('_invoices_view_recurring', ['id' => $newInvoice->getId()]);

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): Generator
            {
                yield FlashResponse::FLASH_SUCCESS => 'invoice.clone.success';
            }
        };
    }
}
