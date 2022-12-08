<?php
declare(strict_types=1);

namespace SolidInvoice\InvoiceBundle\Message;

use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;

final class CreateInvoiceFromRecurring
{
    private RecurringInvoice $recurringInvoice;

    public function __construct(RecurringInvoice $recurringInvoice)
    {
        $this->recurringInvoice = $recurringInvoice;
    }

    public function getRecurringInvoice(): RecurringInvoice
    {
        return $this->recurringInvoice;
    }
}
