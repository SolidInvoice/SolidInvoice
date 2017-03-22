<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Mailer\Events;

use Swift_Message;
use Symfony\Component\EventDispatcher\Event;

abstract class MessageEvent extends Event implements MessageEventInterface
{
    /**
     * @var Swift_Message
     */
    protected $message;

    /**
     * @var string
     */
    protected $htmlTemplate;

    /**
     * @var string
     */
    protected $txtTemplate;

    /**
     * @return mixed
     */
    abstract public function getEvent(): string;

    /**
     * @param Swift_Message $message
     */
    public function setMessage(Swift_Message $message)
    {
        $this->message = $message;
    }

    /**
     * @return Swift_Message
     */
    public function getMessage(): Swift_Message
    {
        return $this->message;
    }

    /**
     * @param string $template
     */
    public function setHtmlTemplate(string $template)
    {
        $this->htmlTemplate = $template;
    }

    /**
     * @return string
     */
    public function getHtmlTemplate(): string
    {
        return $this->htmlTemplate;
    }

    /**
     * @param string $template
     */
    public function setTextTemplate(string $template)
    {
        $this->txtTemplate = $template;
    }

    /**
     * @return string
     */
    public function getTextTemplate(): string
    {
        return $this->txtTemplate;
    }
}
