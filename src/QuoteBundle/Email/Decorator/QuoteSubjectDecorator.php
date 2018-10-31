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

namespace SolidInvoice\QuoteBundle\Email\Decorator;

use SolidInvoice\MailerBundle\Decorator\MessageDecorator;
use SolidInvoice\MailerBundle\Decorator\VerificationMessageDecorator;
use SolidInvoice\MailerBundle\Event\MessageEvent;
use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use SolidInvoice\SettingsBundle\SystemConfig;

final class QuoteSubjectDecorator implements MessageDecorator, VerificationMessageDecorator
{
    /**
     * @var SystemConfig
     */
    private $config;

    public function __construct(SystemConfig $config)
    {
        $this->config = $config;
    }

    public function decorate(MessageEvent $event): void
    {
        /** @var QuoteEmail $message */
        $message = $event->getMessage();
        $message->setSubject(\str_replace('{id}', $message->getQuote()->getId(), $this->config->get('quote/email_subject')));
    }

    public function shouldDecorate(MessageEvent $event): bool
    {
        $message = $event->getMessage();

        return $message instanceof QuoteEmail && null === $message->getSubject();
    }
}
