<?php

namespace CSBill\CoreBundle\Mailer\Events;

use CSBill\CoreBundle\Mailer\MailerEvents;
use CSBill\InvoiceBundle\Entity\Invoice;

class InvoiceEvent extends MessageEvent
{
    /**
     * @var Invoice
     */
    protected $invoice;

    /**
     * @return string
     */
    public function getEvent()
    {
        return MailerEvents::MAILER_SEND_INVOICE;
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
    public function getInvoice()
    {
        return $this->invoice;
    }
}