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

use Symfony\Contracts\EventDispatcher\Event;
use SolidInvoice\MailerBundle\Context;

class MessageEvent extends Event
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
