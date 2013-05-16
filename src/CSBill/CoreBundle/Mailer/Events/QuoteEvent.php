<?php

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
