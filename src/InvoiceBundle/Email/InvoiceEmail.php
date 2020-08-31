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

namespace SolidInvoice\InvoiceBundle\Email;

use SolidInvoice\InvoiceBundle\Entity\Invoice;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Swift_Message;

final class InvoiceEmail extends TemplatedEmail
{
    /**
     * @var Invoice
     */
    private $invoice;

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

    public function getContext(): array
    {
        return \array_merge(['invoice' => $this->invoice], parent::getContext());
    }
}
