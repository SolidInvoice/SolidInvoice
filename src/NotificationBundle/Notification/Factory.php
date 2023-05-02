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

namespace SolidInvoice\NotificationBundle\Notification;

use Namshi\Notificator\NotificationInterface;
use SolidInvoice\SettingsBundle\SystemConfig;
use Swift_Message;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class Factory
{
    public function __construct(
        private readonly Environment $twig,
        private readonly TranslatorInterface $translator,
        private readonly SystemConfig $settings
    ) {
    }

    /**
     * @return SwiftMailerNotification
     */
    public function createEmailNotification(NotificationMessageInterface $message): NotificationInterface
    {
        $swiftMessage = new Swift_Message();

        $from = [$this->settings->get('email/from_address') => $this->settings->get('email/from_name')];

        $swiftMessage->setFrom($from);
        $swiftMessage->setSubject($message->getSubject($this->translator));

        foreach ($message->getUsers() as $user) {
            $swiftMessage->addTo($user->getEmail(), $user->getUsername());
        }

        $swiftMessage->setBody($message->getHtmlContent($this->twig), 'text/html');
        $swiftMessage->addPart($message->getTextContent($this->twig), 'text/plain');

        return new SwiftMailerNotification($swiftMessage);
    }

    /**
     * @return TwilioNotification
     */
    public function createSmsNotification(string $cellphone, NotificationMessageInterface $message): NotificationInterface
    {
        return new TwilioNotification($cellphone, $message->getTextContent($this->twig));
    }
}
