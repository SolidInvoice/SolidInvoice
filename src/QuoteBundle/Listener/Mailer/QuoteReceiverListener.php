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

namespace SolidInvoice\QuoteBundle\Listener\Mailer;

use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use function array_map;
use function in_array;

/**
 * @see \SolidInvoice\QuoteBundle\Tests\Listener\Mailer\QuoteReceiverListenerTest
 */
class QuoteReceiverListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly SystemConfig $config
    ) {
    }

    public function __invoke(MessageEvent $event): void
    {
        /** @var QuoteEmail $message */
        $message = $event->getMessage();

        if (! $message instanceof QuoteEmail) {
            return;
        }

        // A send can name its own recipients; the contacts are only the default.
        if ([] === $message->getTo()) {
            foreach ($message->getQuote()->getUsers() as $user) {
                $message->addTo(new Address($user->getEmail(), trim(sprintf('%s %s', $user->getFirstName(), $user->getLastName()))));
            }
        }

        // Added, not set, so a sender's own copy survives. The event can fire twice for one message.
        $bcc = (string) $this->config->get('quote/bcc_address');

        if ('' !== $bcc && ! in_array($bcc, array_map(static fn (Address $address): string => $address->getAddress(), $message->getBcc()), true)) {
            $message->addBcc($bcc);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => '__invoke',
        ];
    }
}
