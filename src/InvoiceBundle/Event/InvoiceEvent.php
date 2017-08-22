<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Event;

use SolidInvoice\InvoiceBundle\Entity\Invoice;
use Symfony\Component\EventDispatcher\Event;

class InvoiceEvent extends Event
{
    /**
     * @var Invoice
     */
    protected $invoice;

    /**
     * @param Invoice $invoice
     */
    public function __construct(Invoice $invoice = null)
    {
        $this->invoice = $invoice;
    }

    /**
     * @param Invoice $invoice
     */
    public function setInvoice(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * @return Invoice
     */
    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }
}
