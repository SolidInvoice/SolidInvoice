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

namespace SolidInvoice\InvoiceBundle\Listener\Mailer;

use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use function array_map;
use function in_array;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Listener\Mailer\InvoiceReceiverListenerTest
 */
class InvoiceReceiverListener
{
    public function __construct(
        private readonly SystemConfig $config
    ) {
    }

    #[AsEventListener(event: MessageEvent::class)]
    public function __invoke(MessageEvent $event): void
    {
        /** @var InvoiceEmail $message */
        $message = $event->getMessage();

        if (! $message instanceof InvoiceEmail) {
            return;
        }

        // A send can name its own recipients; the contacts are only the default.
        if ([] === $message->getTo()) {
            foreach ($message->getInvoice()->getUsers() as $user) {
                $message->addTo(new Address($user->getEmail(), trim(sprintf('%s %s', $user->getFirstName(), $user->getLastName()))));
            }
        }

        // Added, not set, so a sender's own copy survives. The event can fire twice for one message.
        $bcc = (string) $this->config->get('invoice/bcc_address');

        if ('' !== $bcc && ! in_array($bcc, array_map(static fn (Address $address): string => $address->getAddress(), $message->getBcc()), true)) {
            $message->addBcc($bcc);
        }
    }
}
