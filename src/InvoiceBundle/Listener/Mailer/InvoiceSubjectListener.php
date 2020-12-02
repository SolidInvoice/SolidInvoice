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

namespace SolidInvoice\InvoiceBundle\Listener\Mailer;

use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;

class InvoiceSubjectListener implements EventSubscriberInterface
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
        $message = $event->getMessage();

        if ($message instanceof InvoiceEmail && null === $message->getSubject()) {
            $message->subject(\str_replace('{id}', (string) $message->getInvoice()->getId(), $this->config->get('invoice/email_subject')));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => '__invoke',
        ];
    }
}
