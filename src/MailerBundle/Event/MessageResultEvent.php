<?php

declare(strict_types=1);

/*
 * This file is part of the SwiftMailerHandler project.
 *
 * @author     Pierre du Plessis <open-source@solidworx.co>
 * @copyright  Copyright (c) 2018
 */

namespace SolidInvoice\MailerBundle\Event;

use SolidInvoice\MailerBundle\Context;
use SolidInvoice\MailerBundle\MessageSentResponse;
use Symfony\Component\EventDispatcher\Event;

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