<?php

namespace CSBill\CoreBundle\Mailer\Events;

use CSBill\CoreBundle\Mailer\MailerEvents;
use CSBill\InvoiceBundle\Entity\Invoice;

class InvoiceEvent extends MessageEvent {

    public function getEvent()
    {
        return MailerEvents::MAILER_SEND_INVOICE;
    }

    public function setInvoice(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function getInvoice()
    {
        return $this->invoice;
    }
}