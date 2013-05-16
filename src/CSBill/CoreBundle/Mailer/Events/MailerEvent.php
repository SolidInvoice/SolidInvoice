<?php

namespace CSBill\CoreBundle\Mailer\Events;

use Symfony\Component\EventDispatcher\Event;
use Swift_Message;

class MailerEvent extends Event
{
    protected $message;

    public function setMessage(Swift_Message $message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }
}
