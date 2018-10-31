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

use SolidInvoice\MailerBundle\Decorator\MessageDecorator;
use SolidInvoice\MailerBundle\Decorator\VerificationMessageDecorator;
use SolidInvoice\MailerBundle\Event\MessageEvent;
use SolidInvoice\MailerBundle\Event\MessageResultEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MessageProcessor implements MessageProcessorInterface
{
    /**
     * @var MessageDecorator[]
     */
    private $decorators;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    public function __construct(\Swift_Mailer $mailer, EventDispatcherInterface $eventDispatcher, iterable $decorators)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->mailer = $mailer;
        $this->decorators = $decorators;
    }

    public function process(\Swift_Message $message, Context $context): MessageSentResponse
    {
        $event = new MessageEvent($message, $context);

        $this->eventDispatcher->dispatch('message.decorate', $event);

        foreach ($this->decorators as $decorator) {
            if ($decorator instanceof VerificationMessageDecorator) {
                if ($decorator->shouldDecorate($event)) {
                    $decorator->decorate($event);
                }
            } else {
                $decorator->decorate($event);
            }
        }

        $this->eventDispatcher->dispatch('message.before_send', $event);

        $this->mailer->send($message, $failedRecipients);

        $result = new MessageSentResponse($failedRecipients);

        $this->eventDispatcher->dispatch('message.after_send', new MessageResultEvent($message, $context, $result));

        return $result;
    }
}
