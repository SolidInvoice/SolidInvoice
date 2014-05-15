<?php

namespace CSBill\InvoiceBundle\Event;

use CSBill\InvoiceBundle\Entity\Invoice;
use Symfony\Component\EventDispatcher\Event;

class InvoicePaidEvent extends Event
{
    /**
     * @var Invoice
     */
    protected $invoice;

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
    public function getInvoice()
    {
        return $this->invoice;
    }
} 