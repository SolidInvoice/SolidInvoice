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

namespace SolidInvoice\InvoiceBundle\Email;

use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\ReminderType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

final class InvoiceReminderEmail extends TemplatedEmail
{
    public function __construct(
        private readonly Invoice $invoice,
        private readonly ReminderType $reminderType,
        private readonly ?int $daysUntilDue = null,
    ) {
        parent::__construct();

        $this->htmlTemplate('@SolidInvoiceInvoice/Email/reminder.html.twig');
        $this->textTemplate('@SolidInvoiceInvoice/Email/reminder.text.twig');
        $this->context([
            'invoice' => $this->invoice,
            'reminder_type' => $this->reminderType->value,
            'days_until_due' => $this->daysUntilDue,
        ]);
    }

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }

    public function getReminderType(): ReminderType
    {
        return $this->reminderType;
    }

    public function getDaysUntilDue(): ?int
    {
        return $this->daysUntilDue;
    }
}
