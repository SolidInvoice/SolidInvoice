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

namespace SolidInvoice\QuoteBundle\Email;

use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

final class QuoteEmail extends TemplatedEmail
{
    public function __construct(
        private readonly Quote $quote
    ) {
        parent::__construct();

        $this->htmlTemplate('@SolidInvoiceQuote/Email/quote.html.twig');
        $this->context(['quote' => $this->quote]);
    }

    public function getQuote(): Quote
    {
        return $this->quote;
    }
}
