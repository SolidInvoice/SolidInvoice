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

class MessageEvent extends \Symfony\Component\EventDispatcher\Event
{
    /**
     * @var \Swift_Message
     */
    private $message;

    /**
     * @var Context
     */
    private $context;

    public function __construct(\Swift_Message $message, Context $context)
    {
        $this->message = $message;
        $this->context = $context;
    }

    public function getMessage(): \Swift_Message
    {
        return $this->message;
    }

    public function setMessage(\Swift_Mime_SimpleMessage $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}