<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MailerBundle\Listener;

use SolidInvoice\MailerBundle\Email\MessageSubjectParametersInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Emails set a translation key from the `email` domain as their subject; this translates it
 * before sending. A subject that is not a key (one the user configured) comes back unchanged.
 *
 * @see \SolidInvoice\MailerBundle\Tests\Listener\TranslateEmailSubjectListenerTest
 */
final readonly class TranslateEmailSubjectListener
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    #[AsEventListener(event: MessageEvent::class, priority: -100)]
    public function __invoke(MessageEvent $event): void
    {
        $message = $event->getMessage();

        if (! $message instanceof Email || null === ($subject = $message->getSubject())) {
            return;
        }

        $parameters = $message instanceof MessageSubjectParametersInterface ? $message->getMessageSubjectParameters() : [];

        $message->subject($this->translator->trans($subject, $parameters, 'email'));
    }
}
