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

namespace SolidInvoice\InvoiceBundle\Event;

use SolidInvoice\InvoiceBundle\Entity\Invoice;

class InvoiceEvent extends \Symfony\Contracts\EventDispatcher\Event
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

    public function setInvoice(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }
}
