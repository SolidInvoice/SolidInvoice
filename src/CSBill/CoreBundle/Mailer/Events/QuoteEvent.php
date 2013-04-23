<?php

namespace CSBill\CoreBundle\Mailer\Events;

use CSBill\CoreBundle\Mailer\MailerEvents;
use CSBill\QuoteBundle\Entity\Quote;

class QuoteEvent extends MessageEvent {

    public function getEvent()
    {
        return MailerEvents::MAILER_SEND_QUOTE;
    }

    public function setQuote(Quote $quote)
    {
        $this->quote = $quote;
    }

    public function getQuote()
    {
        return $this->quote;
    }
}