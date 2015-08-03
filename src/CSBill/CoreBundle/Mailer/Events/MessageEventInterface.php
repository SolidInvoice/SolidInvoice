<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Mailer\Events;

use Swift_Message;

interface MessageEventInterface
{
    /**
     * @return string
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
