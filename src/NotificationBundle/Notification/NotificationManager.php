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

use ReflectionObject;
use SolidInvoice\NotificationBundle\Attribute\AsNotification;
use SolidInvoice\NotificationBundle\Configurator\ConfiguratorInterface;
use SolidInvoice\NotificationBundle\Exception\InvalidNotificationMessageException;
use SolidInvoice\NotificationBundle\Repository\UserNotificationRepository;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;

class NotificationManager
{
    /**
     * @param ServiceLocator<ConfiguratorInterface> $transportConfigurations
     */
    public function __construct(
        private readonly NotifierInterface $notifier,
        private readonly UserNotificationRepository $userNotificationRepository,
        #[TaggedLocator(tag: ConfiguratorInterface::DI_TAG, defaultIndexMethod: 'getName')]
        private readonly ServiceLocator $transportConfigurations,
    ) {
    }

    public function sendNotification(NotificationMessage $message): void
    {
        $attributes = (new ReflectionObject($message))->getAttributes(AsNotification::class);

        if ($attributes === []) {
            throw new InvalidNotificationMessageException(sprintf(
                'The notification message "%s" must have the %s set.',
                $message::class,
                AsNotification::class,
            ));
        }

        $event = $attributes[0]->getArguments()['name'] ?? null;

        $userNotifications = $this->userNotificationRepository->findBy(['event' => $event]);

        foreach ($userNotifications as $userNotification) {
            $channels = [];

            if ($userNotification->isEmail()) {
                $channels[] = 'email';
            }

            foreach ($userNotification->getTransports() as $transport) {
                $transportConfiguration = $this->transportConfigurations->get($transport->getTransport());
                assert($transportConfiguration instanceof ConfiguratorInterface);

                $channels[] = sprintf(
                    '%s/%s',
                    match ($transportConfiguration::getType()) {
                        'texter' => 'sms',
                        'chatter' => 'chat',
                        default => $transportConfiguration::getType(),
                    },
                    $transport->getId()->toString(),
                );
            }

            $message->channels($channels);

            $this->notifier->send($message, new Recipient($userNotification->getUser()->getEmail(), (string) $userNotification->getUser()->getMobile()));
        }
    }
}
