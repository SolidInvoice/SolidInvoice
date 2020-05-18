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

namespace SolidInvoice\MailerBundle\Event;

use SolidInvoice\MailerBundle\Context;
use SolidInvoice\MailerBundle\MessageSentResponse;
use Symfony\Contracts\EventDispatcher\Event;

class MessageResultEvent extends Event
{
    /**
     * @var \Swift_Message
     */
    private $message;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var MessageSentResponse
     */
    private $result;

    public function __construct(\Swift_Message $message, Context $context, MessageSentResponse $result)
    {
        $this->message = $message;
        $this->context = $context;
        $this->result = $result;
    }

    public function getMessage(): \Swift_Message
    {
        return $this->message;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getResult(): MessageSentResponse
    {
        return $this->result;
    }
}
