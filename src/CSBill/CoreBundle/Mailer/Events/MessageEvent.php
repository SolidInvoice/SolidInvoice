<?php

namespace CSBill\CoreBundle\Mailer\Events;

use Symfony\Component\EventDispatcher\Event;
use CSBill\CoreBundle\Mailer\MailerEvents;
use Swift_Message;

abstract class MessageEvent extends Event implements MessageEventInterface
{
    protected $message;

    protected $htmlTemplate;

    protected $txtTemplate;

    abstract public function getEvent();

    public function setMessage(Swift_Message $message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setHtmlTemplate($template)
    {
        $this->htmlTemplate = $template;
    }

    public function getHtmlTemplate()
    {
        return $this->htmlTemplate;
    }

    public function setTextTemplate($template)
    {
        $this->txtTemplate = $template;
    }

    public function getTextTemplate()
    {
        return $this->txtTemplate;
    }
}