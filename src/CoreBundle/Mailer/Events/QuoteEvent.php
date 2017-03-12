<?php

declare(strict_types=1);
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Mailer\Events;

use CSBill\CoreBundle\Mailer\MailerEvents;
use CSBill\QuoteBundle\Entity\Quote;

class QuoteEvent extends MessageEvent
{
    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @return string
     */
    public function getEvent()
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
    public function getQuote()
    {
        return $this->quote;
    }
}
