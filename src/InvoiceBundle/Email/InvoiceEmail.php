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

final class InvoiceEmail extends TemplatedEmail
{
    public function __construct(
        private readonly Invoice $invoice
    ) {
        parent::__construct();

        $this->htmlTemplate('@SolidInvoiceInvoice/Email/invoice.html.twig');
        $this->context(['invoice' => $this->invoice]);
    }

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }
}
