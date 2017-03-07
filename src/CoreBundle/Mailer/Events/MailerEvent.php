<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Mailer\Events;

use Swift_Message;
use Symfony\Component\EventDispatcher\Event;

class MailerEvent extends Event
{
    /**
     * @var Swift_Message
     */
    protected $message;

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
    public function getMessage()
    {
        return $this->message;
    }
}
