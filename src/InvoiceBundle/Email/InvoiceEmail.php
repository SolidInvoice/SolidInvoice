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
use SolidInvoice\MailerBundle\Template\HtmlTemplateMessage;
use SolidInvoice\MailerBundle\Template\Template;

final class InvoiceEmail extends \Swift_Message implements HtmlTemplateMessage
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

    public function getHtmlTemplate(): Template
    {
        return new Template('@SolidInvoiceInvoice/Email/invoice.html.twig', ['invoice' => $this->invoice]);
    }
}
