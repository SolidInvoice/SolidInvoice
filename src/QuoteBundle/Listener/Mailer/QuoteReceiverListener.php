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

/**
 * @see \SolidInvoice\QuoteBundle\Tests\Listener\Mailer\QuoteReceiverListenerTest
 */
class QuoteReceiverListener implements EventSubscriberInterface
{
    /**
     * @var SystemConfig
     */
    private $config;

    public function __construct(SystemConfig $config)
    {
        $this->config = $config;
    }

    public function __invoke(MessageEvent $event): void
    {
        /** @var QuoteEmail $message */
        $message = $event->getMessage();

        if ($message instanceof QuoteEmail && [] === $message->getTo()) {
            $quote = $message->getQuote();

            foreach ($quote->getUsers() as $user) {
                $message->addTo(new Address($user->getEmail(), trim(sprintf('%s %s', $user->getFirstName(), $user->getLastName()))));
            }

            if ('' !== ($bcc = (string) $this->config->get('quote/bcc_address'))) {
                $message->bcc($bcc);
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
