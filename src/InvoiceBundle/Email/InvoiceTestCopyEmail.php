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

/**
 * An invoice sent to the signed-in user's own inbox, carrying the link that confirms their address.
 * Never addressed to the client: it is the one send allowed before the email is verified.
 */
final class InvoiceTestCopyEmail extends TemplatedEmail
{
    public function __construct(
        private readonly Invoice $invoice,
        string $verifyUrl,
    ) {
        parent::__construct();

        $this->htmlTemplate('@SolidInvoiceInvoice/Email/test_copy.html.twig');
        $this->context(['invoice' => $this->invoice, 'verifyUrl' => $verifyUrl]);
    }

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }
}
