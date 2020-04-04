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

namespace SolidInvoice\MailerBundle;

final class Mailer implements MailerInterface
{
    /**
     * @var MessageProcessorInterface
     */
    private $processor;

    public function __construct(MessageProcessorInterface $processor)
    {
        $this->processor = $processor;
    }

    /**
     * @param array $parameters Add additional context to the message
     */
    public function send(\Swift_Message $message, array $parameters = []): MessageSentResponse
    {
        return $this->processor->process($message, Context::create($parameters));
    }
}
