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

namespace SolidInvoice\NotificationBundle\Notification;

use Namshi\Notificator\NotificationInterface;
use SolidInvoice\SettingsBundle\Exception\InvalidSettingException;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class Factory
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var SystemConfig
     */
    private $settings;

    public function __construct(Environment $twig, TranslatorInterface $translator, SystemConfig $settings)
    {
        $this->twig = $twig;
        $this->translator = $translator;
        $this->settings = $settings;
    }

    /**
     * @return SwiftMailerNotification
     *
     * @throws \SolidInvoice\SettingsBundle\Exception\InvalidSettingException
     * @throws InvalidSettingException
     */
    public function createEmailNotification(NotificationMessageInterface $message): NotificationInterface
    {
        $swiftMessage = new \Swift_Message();

        $from = [$this->settings->get('email/from_address') => $this->settings->get('email/from_name')];

        $swiftMessage->setFrom($from);
        $swiftMessage->setSubject($message->getSubject($this->translator));

        foreach ($message->getUsers() as $user) {
            $swiftMessage->addTo($user->getEmail(), $user->getUsername());
        }

        $format = (string) $this->settings->get('email/format');

        switch ($format) {
            case 'html':
                $swiftMessage->setBody($message->getHtmlContent($this->twig), 'text/html');

                break;

            case 'text':
                $swiftMessage->setBody($message->getTextContent($this->twig), 'text/plain');

                break;

            case 'both':
            default:
                $swiftMessage->setBody($message->getHtmlContent($this->twig), 'text/html');
                $swiftMessage->addPart($message->getTextContent($this->twig), 'text/plain');

                break;
        }

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
