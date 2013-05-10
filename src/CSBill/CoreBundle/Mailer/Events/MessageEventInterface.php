<?php
namespace CSBill\CoreBundle\Mailer\Events;

use Swift_Message;

interface MessageEventInterface
{
    /**
     * @return mixed
     */
    public function getEvent();

    /**
     * @param Swift_Message $message
     */
    public function setMessage(Swift_Message $message);

    /**
     * @return Swift_Message
     */
    public function getMessage();

    /**
     * @param string $template
     */
    public function setHtmlTemplate($template);

    /**
     * @return string
     */
    public function getHtmlTemplate();

    /**
     * @param string $template
     */
    public function setTextTemplate($template);

    /**
     * @return string
     */
    public function getTextTemplate();
}