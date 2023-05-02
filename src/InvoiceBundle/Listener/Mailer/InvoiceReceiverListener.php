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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Listener\Mailer\InvoiceReceiverListenerTest
 */
class InvoiceReceiverListener implements EventSubscriberInterface
{
    public function __construct(private readonly SystemConfig $config)
    {
    }

    public function __invoke(MessageEvent $event): void
    {
        /** @var InvoiceEmail $message */
        $message = $event->getMessage();

        if ($message instanceof InvoiceEmail && [] === $message->getTo()) {
            $invoice = $message->getInvoice();

            foreach ($invoice->getUsers() as $user) {
                $message->addTo(new Address($user->getEmail(), trim(sprintf('%s %s', $user->getFirstName(), $user->getLastName()))));
            }

            if ('' !== ($bcc = (string) $this->config->get('invoice/bcc_address'))) {
                $message->addBcc($bcc);
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => '__invoke',
        ];
    }
}
