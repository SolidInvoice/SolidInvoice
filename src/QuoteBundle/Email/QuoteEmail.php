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

namespace SolidInvoice\QuoteBundle\Email;

use SolidInvoice\MailerBundle\Template\HtmlTemplateMessage;
use SolidInvoice\MailerBundle\Template\Template;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Swift_Message;

final class QuoteEmail extends Swift_Message implements HtmlTemplateMessage
{
    /**
     * @var Quote
     */
    private $quote;

    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
        parent::__construct();
    }

    public function getQuote(): Quote
    {
        return $this->quote;
    }

    public function getHtmlTemplate(): Template
    {
        return new Template('@SolidInvoiceQuote/Email/quote.html.twig', ['quote' => $this->quote]);
    }
}
