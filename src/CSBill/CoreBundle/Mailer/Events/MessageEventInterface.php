<?php
namespace CSBill\CoreBundle\Mailer\Events;

use Swift_Message;

interface MessageEventInterface
{
    public function getEvent();

    public function setMessage(Swift_Message $message);

    public function getMessage();

    public function setHtmlTemplate($template);

    public function getHtmlTemplate();

    public function setTextTemplate($template);

    public function getTextTemplate();
}