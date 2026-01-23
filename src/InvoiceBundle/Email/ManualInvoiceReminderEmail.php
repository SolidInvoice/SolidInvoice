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

use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

final class ManualInvoiceReminderEmail extends TemplatedEmail
{
    public function __construct(
        private readonly Invoice $invoice
    ) {
        parent::__construct();

        $this->subject("Payment Reminder: Invoice {$invoice->getInvoiceId()}");
        $this->htmlTemplate('@SolidInvoiceInvoice/Email/manual_reminder.html.twig');
        $this->textTemplate('@SolidInvoiceInvoice/Email/manual_reminder.text.twig');
        $this->context(['invoice' => $this->invoice]);
        $this->to(...$this->invoice->getUsers()->map(fn (Contact $user) => Address::create(sprintf('%s %s <%s>', $user->getFirstName(), $user->getLastName(), $user->getEmail())))->toArray());
    }

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }
}
