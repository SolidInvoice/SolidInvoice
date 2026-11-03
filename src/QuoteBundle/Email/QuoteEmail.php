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

use SolidInvoice\CoreBundle\Contracts\ClientEmailInterface;
use SolidInvoice\CoreBundle\Mailer\DocumentSendOptions;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

final class QuoteEmail extends TemplatedEmail implements ClientEmailInterface
{
    public function __construct(
        private readonly Quote $quote,
        private readonly ?DocumentSendOptions $options = null,
    ) {
        parent::__construct();

        $this->htmlTemplate('@SolidInvoiceQuote/Email/quote.html.twig');
        $this->context(['quote' => $this->quote]);
        $this->options?->applyTo($this);
    }

    public function attachesPdf(): bool
    {
        return $this->options->attachPdf ?? true;
    }

    public function getQuote(): Quote
    {
        return $this->quote;
    }
}
