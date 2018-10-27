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
use SolidInvoice\MailerBundle\Template\TextTemplateMessage;
use SolidInvoice\QuoteBundle\Entity\Quote;

final class QuoteEmail extends \Swift_Message implements HtmlTemplateMessage, TextTemplateMessage
{
    /**
     * @var Quote
     */
    private $quote;

    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
    }

    /**
     * @return Quote
     */
    public function getQuote(): Quote
    {
        return $this->quote;
    }

    public function getHtmlTemplate(): string
    {
        return '@SolidInvoiceQuote/Email/quote.html.twig';
    }

    public function getTextTemplate(): string
    {
        return '@SolidInvoiceQuote/Email/quote.txt.twig';
    }
}
