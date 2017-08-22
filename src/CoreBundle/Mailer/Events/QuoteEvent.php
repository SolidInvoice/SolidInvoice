<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Mailer\Events;

use SolidInvoice\CoreBundle\Mailer\MailerEvents;
use SolidInvoice\QuoteBundle\Entity\Quote;

class QuoteEvent extends MessageEvent
{
    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @return string
     */
    public function getEvent(): string
    {
        return MailerEvents::MAILER_SEND_QUOTE;
    }

    /**
     * @param Quote $quote
     */
    public function setQuote(Quote $quote)
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
}
