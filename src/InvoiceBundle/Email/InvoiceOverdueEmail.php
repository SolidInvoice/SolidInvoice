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
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use function sprintf;

final class InvoiceOverdueEmail extends TemplatedEmail
{
    public function __construct(
        private readonly Invoice $invoice
    ) {
        parent::__construct();

        $this->htmlTemplate('@SolidInvoiceInvoice/Email/overdue.html.twig');
        $this->textTemplate('@SolidInvoiceInvoice/Email/overdue.text.twig');
        $this->context(['invoice' => $this->invoice]);
        $this->subject(sprintf('Payment Overdue: Invoice %s', $this->invoice->getInvoiceId()));
        $this->priority(TemplatedEmail::PRIORITY_HIGH);
    }

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }
}
