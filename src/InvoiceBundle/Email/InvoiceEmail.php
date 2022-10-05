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

use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

final class InvoiceEmail extends TemplatedEmail
{
    private Invoice $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
        parent::__construct();
    }

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }

    public function getHtmlTemplate(): string
    {
        return '@SolidInvoiceInvoice/Email/invoice.html.twig';
    }

    /**
     * @return array{invoice: Invoice}
     */
    public function getContext(): array
    {
        return \array_merge(['invoice' => $this->invoice], parent::getContext());
    }
}
